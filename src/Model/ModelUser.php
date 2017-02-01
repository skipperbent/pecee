<?php
namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Cookie;
use Pecee\Guid;
use Pecee\Model\User\UserBadLogin;
use Pecee\Model\User\UserData;
use Pecee\Model\User\UserException;

class ModelUser extends ModelData
{
    const COOKIE_NAME = 'ticket';

    /* Errors */
    const ERROR_TYPE_BANNED = 0x1;
    const ERROR_TYPE_INVALID_LOGIN = 0x2;
    const ERROR_TYPE_EXISTS = 0x3;

    protected static $instance;
    protected static $ticketExpireMinutes = 60 * 1;

    protected $table = 'user';

    protected $columns = [
        'id',
        'username',
        'password',
        'admin_level',
        'deleted',
        'last_activity',
    ];

    public function __construct($username = null, $password = null)
    {
        parent::__construct();

        $this->username = $username;

        if ($password !== null) {
            $this->password = $this->setPassword($password);
        }

        $this->admin_level = 0;
        $this->deleted = false;
        $this->last_activity = Carbon::now();
    }

    public function save(array $data = null)
    {
        if ($this->{$this->primary} === null) {
            $user = $this->instance()->filterUsername($this->username)->first();
            if ($user !== null && $user->id !== $this->id) {
                throw new UserException(sprintf('The username %s already exists', $this->data->username), static::ERROR_TYPE_EXISTS);
            }
        }
        parent::save($data);
    }

    protected function getDataClass()
    {
        return static::getUserDataClass();
    }

    protected function onNewDataItemCreate(Model &$data)
    {
        $data->{$data::USER_IDENTIFIER_KEY} = $this->id;
        parent::onNewDataItemCreate($data);
    }

    protected function fetchData()
    {
        $class = static::getUserDataClass();

        return $class::getByIdentifier($this->id);
    }

    public function delete()
    {
        $this->deleted = true;
        $this->save();
    }

    /**
     * Validates cookie-ticket and returns bool if the ticket is valid.
     *
     * @return self|bool
     */
    public static function isLoggedIn()
    {
        $ticket = static::getTicket();

        try {

            if ($ticket === null || Carbon::parse($ticket[1])->diffInMinutes(Carbon::now()) > static::$ticketExpireMinutes) {
                Cookie::delete(static::COOKIE_NAME);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Cookie::delete(static::COOKIE_NAME);

            return false;
        }
    }

    public static function createTicket($userId)
    {
        /* Remove existing ticket */
        Cookie::delete(static::COOKIE_NAME);

        $ticket = Guid::encrypt(static::getSalt(), join('|', [
            $userId,
            Carbon::now()->addMinutes(static::$ticketExpireMinutes)->toW3cString(),
        ]));

        Cookie::create(static::COOKIE_NAME, $ticket);
    }

    public static function getTicket()
    {
        if (Cookie::exists(static::COOKIE_NAME) === false) {
            return null;
        }

        $ticket = Guid::decrypt(static::getSalt(), Cookie::get(static::COOKIE_NAME));

        if ($ticket !== false) {
            $ticket = explode('|', $ticket);

            return (count($ticket) > 0) ? $ticket : null;
        }

        return null;
    }

    protected function signIn()
    {
        static::createTicket($this->id);
    }

    public function signOut()
    {
        Cookie::delete(static::COOKIE_NAME);
    }

    public function exist()
    {
        return $this->filterUsername($this->username)->filterDeleted(false)->first();
    }

    public function registerActivity()
    {
        if ($this->isLoggedIn() === true) {
            $this->last_activity = Carbon::now()->toDateTimeString();
            $this->save();
        }
    }

    /**
     * Sets users password and encrypts it.
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public static function getSalt()
    {
        return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
    }

    /**
     * Get current user
     *
     * @return static
     */
    public static function current()
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        if (static::isLoggedIn() === true) {

            $ticket = static::getTicket();

            /* @var $user static */
            static::$instance = static::instance()->filterDeleted()->find($ticket[0]);

            if (static::$instance !== null) {
                /* Refresh ticket */
                static::createTicket($ticket[0]);
            }
        }

        return static::$instance;
    }

    public static function getSecret()
    {
        return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
    }

    public function filterQuery($query)
    {
        $userDataClassName = $this->getUserDataClass();
        /* @var $userDataClass UserData */
        $userDataClass = new $userDataClassName();

        $userDataQuery = $this->newQuery($userDataClass->getTable())
            ->getQuery()
            ->select($userDataClassName::USER_IDENTIFIER_KEY)
            ->where($userDataClassName::USER_IDENTIFIER_KEY, '=', static::getQuery()->raw($this->getTable() . '.' . $this->getPrimary()))
            ->where('value', 'LIKE', '%' . str_replace('%', '%%', $query) . '%')
            ->limit(1);

        return $this->where('username', 'LIKE', '%' . str_replace('%', '%%', $query) . '%')
            ->orWhere($this->getPrimary(), '=', $this->raw($userDataQuery));
    }

    public function filterDeleted($deleted = false)
    {
        return $this->where('deleted', '=', $deleted);
    }

    public function filterAdminLevel($level)
    {
        return $this->where('admin_level', '=', $level);
    }

    public function filterUsername($username)
    {
        return $this->where('username', '=', $username);
    }

    public function filterPassword($password)
    {
        return $this->where('password', '=', md5($password));
    }

    public function filterKeyValue($key, $value, $like = false)
    {
        $userDataClassName = static::getUserDataClass();
        /* @var $userDataClass UserData */
        $userDataClass = new $userDataClassName();

        $subQuery = $userDataClass::instance()->select([$userDataClass::USER_IDENTIFIER_KEY])->where('key', '=', $key)->where('value', (($like) ? 'LIKE' : '='), (string)$value);

        return $this->where($this->primary, '=', $this->subQuery($subQuery));
    }

    public static function getByUsername($username)
    {
        return static::instance()->filterDeleted(false)->filterUsername($username);
    }

    public static function authenticate($username, $password)
    {
        static::onLoginStart($username, $password);

        /* @var $user ModelUser */
        $user = static::instance()->filterDeleted(false)->filterUsername($username)->first();

        if ($user === null) {
            throw new UserException('User does not exist', static::ERROR_TYPE_EXISTS);
        }

        // Incorrect user login.
        if (password_verify($password, $user->password) === false || strtolower($user->username) !== strtolower($username)) {
            static::onLoginFailed($user);
            throw new UserException('Invalid login', static::ERROR_TYPE_INVALID_LOGIN);
        }

        static::onLoginSuccess($user);
        $user->signIn();

        return $user;
    }

    public function auth()
    {
        return static::authenticate($this->username, $this->password);
    }

    /**
     * @return UserData
     */
    public static function getUserDataClass()
    {
        return UserData::class;
    }

    // Events
    protected static function onLoginFailed(ModelUser $user)
    {
        UserBadLogin::track($user->username);
    }

    protected static function onLoginSuccess(ModelUser $user)
    {
        UserBadLogin::reset($user->username);
    }

    protected static function onLoginStart($username, $password)
    {
        if (UserBadLogin::checkBadLogin($username)) {
            throw new UserException('User has been banned', static::ERROR_TYPE_BANNED);
        }
    }
}