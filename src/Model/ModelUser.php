<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Cookie;
use Pecee\Guid;
use Pecee\Model\User\UserBadLogin;
use Pecee\Model\User\UserData;
use Pecee\Model\User\UserException;
use Pecee\User\IUserAuthentication;

class ModelUser extends ModelData implements IUserAuthentication
{
    public const COOKIE_NAME = 'ticket';

    /* Errors */
    public const ERROR_TYPE_BANNED = 0x1;
    public const ERROR_TYPE_INVALID_LOGIN = 0x2;
    public const ERROR_TYPE_EXISTS = 0x3;

    protected static $instance;
    protected static $ticketExpireMinutes = 60;

    protected $dataPrimary = 'user_id';
    protected $table = 'user';
    protected $columns = [
        'id',
        'username',
        'password',
        'admin_level',
        'last_activity',
        'deleted',
    ];

    public function __construct($username = null, $password = null)
    {
        parent::__construct();

        $this->username = $username;

        if ($password !== null) {
            $this->setPassword($password);
        }

        $this->admin_level = 0;
        $this->deleted = false;
        $this->last_activity = Carbon::now();
    }

    protected function getDataClass()
    {
        return static::getUserDataClass();
    }

    protected function fetchData(): \IteratorAggregate
    {
        $class = static::getUserDataClass();
        return $class::instance()->filterIdentifier($this->id)->all();
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

    public static function getTicket(): ?array
    {
        if (Cookie::exists(static::COOKIE_NAME) === false) {
            return null;
        }

        return static::parseTicket(Cookie::get(static::COOKIE_NAME));
    }

    public static function parseTicket(string $ticket): ?array
    {
        $ticket = Guid::decrypt(static::getSalt(), $ticket);

        if ($ticket !== false) {
            $ticket = explode('|', $ticket);

            return (count($ticket) > 0) ? $ticket : null;
        }

        return null;
    }

    public function signIn()
    {
        static::createTicket($this->id);
    }

    public function signOut()
    {
        Cookie::delete(static::COOKIE_NAME);
    }

    public function exist()
    {
        if ($this->{$this->primaryKey} === null) {
            $user = static::instance()->filterUsername($this->username)->first();
            if ($user !== null && $user->id !== $this->id) {
                return true;
            }
        }

        return false;
    }

    public function registerActivity()
    {
        $this->last_activity = Carbon::now();
        $this->save();
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
        if (static::$instance === null && static::isLoggedIn() === true) {
            $ticket = static::getTicket();
            static::$instance = static::instance()->filterDeleted()->find($ticket[0]);
        }

        if (static::$instance !== null) {
            /* Refresh ticket */
            static::createTicket(static::$instance->id);
        }

        return static::$instance;
    }

    public function filterQuery($query)
    {
        $userDataQuery = static::instance()
            ->getQuery()
            ->select($this->getDataPrimary())
            ->where($this->getDataPrimary(), '=', static::instance()->getQuery()->raw($this->getTable() . '.' . $this->getDataPrimary()))
            ->where('value', 'LIKE', '%' . str_replace('%', '%%', $query) . '%')
            ->limit(1);

        return $this
            ->where('username', 'LIKE', '%' . str_replace('%', '%%', $query) . '%')
            ->orWhere($this->getDataPrimary(), '=', $this->raw($userDataQuery));
    }

    /**
     * @param bool $deleted
     * @return static
     */
    public function filterDeleted(bool $deleted = false)
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

        $subQuery = $userDataClass::instance()->select([$this->getDataPrimary()])->where('key', '=', $key)->where('value', ($like ? 'LIKE' : '='), (string)$value);

        return $this->where($this->primaryKey, '=', $this->subQuery($subQuery));
    }

    public static function getByUsername($username)
    {
        return static::instance()->filterDeleted()->filterUsername($username);
    }

    public static function authenticate($username, $password)
    {
        static::onLoginStart($username, $password);

        /* @var $user static */
        $user = static::instance()->filterDeleted()->filterUsername($username)->first();

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
        $this->signIn();
    }

    /**
     * @return string
     */
    public static function getUserDataClass()
    {
        return UserData::class;
    }

    // Events
    protected static function onLoginFailed(self $user)
    {
        UserBadLogin::track($user->username);
    }

    protected static function onLoginSuccess(self $user)
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