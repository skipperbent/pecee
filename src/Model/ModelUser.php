<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Cookie;
use Pecee\Guid;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\ModelMeta\IModelMetaField;
use Pecee\Model\User\UserBadLogin;
use Pecee\Model\User\UserData;
use Pecee\Model\User\UserException;
use Pecee\User\IUserAuthentication;

/**
 * Class ModelUser
 * @package Pecee\Model
 * @property int $id
 * @property string $username
 * @property string $password
 * @property int $admin_level
 * @property string $last_activity
 * @property bool $deleted
 */
class ModelUser extends ModelMeta implements IUserAuthentication
{
    public const COOKIE_NAME = 'ticket';

    /* Errors */
    public const ERROR_TYPE_BANNED = 0x1;
    public const ERROR_TYPE_INVALID_LOGIN = 0x2;
    public const ERROR_TYPE_EXISTS = 0x3;

    /**
     * @var self
     */
    protected static ?self $instance = null;
    protected static int $ticketExpireMinutes = 60;

    protected string $dataPrimary = 'user_id';
    protected string $table = 'user';
    protected array $columns = [
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

    protected function fetchData(): ModelCollection
    {
        return UserData::instance()->filterUserId($this->id)->all();
    }

    public function delete()
    {
        $this->deleted = true;
        $this->save();
    }

    /**
     * Validates cookie-ticket and returns bool if the ticket is valid.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
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

    public static function createTicket(string $userId): void
    {
        $ticket = Guid::encrypt(static::getSalt(), implode('|', [
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

        $ticket = Guid::decrypt(static::getSalt(), Cookie::get(static::COOKIE_NAME));

        if ($ticket !== false) {
            $ticket = explode('|', $ticket);

            return (count($ticket) > 0) ? $ticket : null;
        }

        return null;
    }

    public function signIn(): void
    {
        static::createTicket($this->id);
    }

    public function signOut(): void
    {
        Cookie::delete(static::COOKIE_NAME);
    }

    public function exist(): bool
    {
        if ($this->{$this->primaryKey} === null) {
            $user = static::instance()->select(['id'])->filterUsername($this->username)->first();
            if ($user !== null && $user->id !== $this->id) {
                return true;
            }
        }

        return false;
    }

    public function registerActivity(): void
    {
        $this->last_activity = Carbon::now()->toDateTimeString();
        $this->save();
    }

    /**
     * Sets users password and encrypts it.
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public static function getSalt(): string
    {
        return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
    }

    /**
     * Get current user
     *
     * @return static|null
     */
    public static function current(): ?self
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

    public function filterQuery(string $query): self
    {
        $identifierName = $this->onNewDataItem()->getDataKeyName();

        $userDataQuery = static::instance()
            ->getQuery()
            ->select($identifierName)
            ->where($identifierName, '=', static::instance()->getQuery()->raw($this->getTable() . '.' . $identifierName))
            ->where('value', 'LIKE', '%' . str_replace('%', '%%', $query) . '%')
            ->limit(1);

        return $this
            ->where('username', 'LIKE', '%' . str_replace('%', '%%', $query) . '%')
            ->orWhere($identifierName, '=', $this->raw($userDataQuery));
    }

    /**
     * @param bool $deleted
     * @return static
     */
    public function filterDeleted(bool $deleted = false): self
    {
        return $this->where('deleted', '=', $deleted);
    }

    public function filterAdminLevel(int $level): self
    {
        return $this->where('admin_level', '=', $level);
    }

    public function filterUsername(string $username): self
    {
        return $this->where('username', '=', $username);
    }

    public function filterPassword(string $password): self
    {
        return $this->where('password', '=', md5($password));
    }

    public function filterKeyValue(string $key, string $value, bool $like = false): self
    {
        /* @var $userDataClass UserData */
        $userDataClass = $this->onNewDataItem();

        $subQuery = $userDataClass::instance()->select([$userDataClass->getDataKeyName()])->where('key', '=', $key)->where('value', ($like ? 'LIKE' : '='), (string)$value);

        return $this->where($this->primaryKey, '=', $this->subQuery($subQuery));
    }

    public static function getByUsername(string $username): self
    {
        return static::instance()->filterDeleted()->filterUsername($username);
    }

    public static function authenticate(string $username, string $password): self
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

    public function auth(): void
    {
        $this->signIn();
    }

    // Events
    protected static function onLoginFailed(self $user): void
    {
        UserBadLogin::track($user->username);
    }

    protected static function onLoginSuccess(self $user): void
    {
        UserBadLogin::reset($user->username);
    }

    protected static function onLoginStart(string $username, string $password): void
    {
        if (UserBadLogin::checkBadLogin($username)) {
            throw new UserException('User has been banned', static::ERROR_TYPE_BANNED);
        }
    }

    protected function onNewDataItem(): IModelMetaField
    {
        $data = new UserData();
        $data->user_id = $this->id;
        return $data;
    }
}