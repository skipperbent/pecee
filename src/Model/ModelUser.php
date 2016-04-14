<?php
namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Cookie;
use Pecee\Mcrypt;
use Pecee\Model\User\UserBadLogin;
use Pecee\Model\User\UserData;
use Pecee\Model\User\UserException;

class ModelUser extends ModelData {

    const COOKIE_NAME = 'ticket';

    // Errors
    const ERROR_TYPE_BANNED = 0x1;
    const ERROR_TYPE_INVALID_LOGIN = 0x2;
    const ERROR_TYPE_EXISTS = 0x3;
    protected static $instance;

    protected $columns = [
        'id',
        'username',
        'password',
        'admin_level',
        'deleted',
        'created_date',
        'last_activity'
    ];

    public function __construct($username = null, $password = null) {

        parent::__construct();

        $this->username = $username;
        $this->password = md5($password);
        $this->admin_level = 0;
        $this->deleted = false;
        $this->last_activity = Carbon::now()->toDateTimeString();
        $this->created_date = Carbon::now()->toDateTimeString();
    }

    public function save() {
        $user = $this->filterUsername($this->username)->first();
        if($user != null && $user->id != $this->id) {
            throw new UserException(sprintf('The username %s already exists', $this->data->username), static::ERROR_TYPE_EXISTS);
        }
        parent::save();
    }

    public function updateData() {

        if($this->data !== null) {

            $userDataClass = static::getUserDataClass();
            $currentFields = $userDataClass::getByIdentifier($this->id);

            $cf = array();
            foreach($currentFields as $field) {
                $cf[strtolower($field->key)] = $field;
            }

            if(count($this->data->getData())) {

                foreach($this->data->getData() as $key => $value) {

                    if($value === null) {
                        continue;
                    }

                    if(isset($cf[strtolower($key)])) {
                        if($cf[$key]->value === $value) {
                            unset($cf[$key]);
                            continue;
                        } else {
                            $cf[$key]->value = $value;
                            $cf[$key]->key = $key;
                            $cf[$key]->save();
                            unset($cf[$key]);
                        }
                    } else {
                        /* @var $field UserData */
                        $field = new $userDataClass();
                        $field->{$userDataClass::USER_IDENTIFIER_KEY} = $this->id;
                        $field->key = $key;
                        $field->value = $value;
                        $field->save();
                    }
                }
            }

            foreach($cf as $field) {
                $field->delete();
            }
        }
    }

    protected function fetchData() {
        $class = static::getUserDataClass();
        $data = $class::getByIdentifier($this->id);
        if($data->hasRows()) {
            foreach($data->getRows() as $d) {
                $this->setDataValue($d->key, $d->value);
            }
        }
    }

    public function delete() {
        $this->deleted = true;
        $this->save();
    }


    public static function isLoggedIn($force = false) {
        if($force === true) {
            $user = static::getFromCookie(true);
            return ($user !== null);
        }
        return (Cookie::exists(static::COOKIE_NAME) && static::getFromCookie() !== null);
    }

    public function signOut() {
        if(Cookie::exists(static::COOKIE_NAME)) {
            Cookie::delete(static::COOKIE_NAME);
        }
    }

    public function exist() {
        return $this->filterUsername($this->username)->filterDeleted(false)->first();
    }

    public function registerActivity() {
        if($this->isLoggedIn()) {
            $this->last_activity = Carbon::now()->toDateTimeString();
            $this->save();
        }
    }

    protected function signIn($cookieExp){
        $user = array($this->id, $this->password, md5(microtime()), $this->username, $this->admin_level, static::getSalt());
        $ticket = Mcrypt::encrypt(join('|',$user), static::getSalt());
        Cookie::create(static::COOKIE_NAME, $ticket, $cookieExp);
    }

    /**
     * Set timeout on user session
     * @param int $minutes
     */
    public function setTimeout($minutes) {
        $this->signIn(time()+60*$minutes);
    }

    /**
     * Sets users password and encrypts it.
     * @param string $string
     */
    public function setPassword($string) {
        $this->password = md5($string);
    }

    public static function getFromCookie($setData = false) {
        $ticket = Cookie::get(static::COOKIE_NAME);
        if(trim($ticket) !== ''){
            $ticket = Mcrypt::decrypt($ticket, static::getSalt());
            $user = explode('|', $ticket);
            if (is_array($user) && trim(end($user)) === static::getSalt()) {
                if ($setData) {
                    static::$instance = static::find($user[0]);
                    return static::$instance;
                } else {
                    $obj = new static();
                    $obj->setRow('id', $user[0]);
                    $obj->setRow('password', $user[1]);
                    $obj->setRow('username', $user[3]);
                    $obj->setRow('admin_level', $user[4]);
                    return $obj;
                }
            }
        }
        return null;
    }

    /**
     * Get current user
     * @param bool $setData
     * @return static
     */
    public static function current($setData = false) {
        if(!is_null(static::$instance)) {
            return static::$instance;
        }
        if(static::isLoggedIn()){
            $user = static::getFromCookie($setData);
            if($user !== null) {
                return $user;
            }
        }
        return static::$instance;
    }

    public static function getSalt() {
        return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
    }

    public function filterQuery($query) {

        $userDataClassName = $this->getUserDataClass();
        /* @var $userDataClass UserData */
        $userDataClass = new $userDataClassName();

        $userDataQuery = $this->newQuery($userDataClass->getTable())
            ->getQuery()
            ->select($userDataClassName::USER_IDENTIFIER_KEY)
            ->where($userDataClassName::USER_IDENTIFIER_KEY, '=', static::getQuery()->raw($this->getTable() . '.' . $this->getPrimary()))
            ->where('value', 'LIKE', '%'. str_replace('%', '%%', $query) .'%')
            ->limit(1);

        return $this->where('username', 'LIKE', '%'.str_replace('%', '%%', $query).'%')
            ->orWhere($this->getPrimary(), '=', $this->raw($userDataQuery));
    }

    public function filterDeleted($deleted) {
        return $this->where('deleted', '=', $deleted);
    }

    public function filterAdminLevel($level) {
        return $this->where('admin_level', '=', $level);
    }

    public function filterUsername($username) {
        return $this->where('username', '=', $username);
    }

    public function filterKeyValue($key, $value) {
        $userDataClassName = static::getUserDataClass();
        /* @var $userDataClass UserData */
        $userDataClass = new $userDataClassName();

        return $this->join($userDataClass->getTable(), $userDataClassName::USER_IDENTIFIER_KEY, '=', $this->getTable() . '.' . $this->getPrimary())
            ->where($userDataClass->getTable() . '.' . 'key', $key)
            ->where($userDataClass->getTable() . '.' . 'value', $value);
    }

    public static function getByUsername($username) {
        return static::instance()->filterDeleted(false)->filterUsername($username);
    }

    public static function authenticate($username, $password, $remember = false) {

        static::onLoginStart($username, $password, $remember);

        /* @var $user ModelUser */
        $user = static::where('username', '=', $username)->first();

        if($user === null) {
            throw new UserException('Invalid login', static::ERROR_TYPE_INVALID_LOGIN);
        }

        // Incorrect user login.
        if(strtolower($user->username) != strtolower($username) || $user->password != md5($password) && $user->password != $password) {
            static::onLoginFailed($user);
            throw new UserException('Invalid login', static::ERROR_TYPE_INVALID_LOGIN);
        }

        static::onLoginSuccess($user);
        $user->signIn(($remember) ? null : 0);
        return $user;
    }

    public function auth() {
        return static::authenticate($this->username, $this->password, false);
    }

    /**
     * @return UserData
     */
    public static function getUserDataClass() {
        return UserData::class;
    }

    // Events
    protected static function onLoginFailed(ModelUser $user){
        UserBadLogin::track($user->username);
    }

    protected static function onLoginSuccess(ModelUser $user) {
        UserBadLogin::reset($user->username);
    }

    protected static function onLoginStart($username, $password, $remember) {
        if(UserBadLogin::checkBadLogin($username)) {
            throw new UserException('User has been banned', static::ERROR_TYPE_BANNED);
        }
    }
}