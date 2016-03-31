<?php
namespace Pecee\Model;

use Pecee\Cookie;
use Pecee\Date;
use Pecee\DB\PdoHelper;
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

    const ORDER_ID_DESC = 'u.`id` DESC';
    const ORDER_ID_ASC = 'u.`id` ASC';
    const ORDER_LASTACTIVITY_ASC = 'u.`last_activity` DESC';
    const ORDER_LASTACTIVITY_DESC = 'u.`last_activity` ASC';

    protected static $instance;

    public static $ORDERS = [
        self::ORDER_ID_ASC,
        self::ORDER_ID_DESC,
        self::ORDER_LASTACTIVITY_ASC,
        self::ORDER_LASTACTIVITY_DESC
    ];

    protected $columns = [
        'id',
        'username',
        'password',
        'last_activity',
        'admin_level',
        'deleted'
    ];

    public function __construct($username = null, $password = null, $email = null) {

        parent::__construct();

        $this->username = $username;
        $this->password = md5($password);
        $this->admin_level = 0;
        $this->last_activity = Date::toDateTime();
        $this->deleted = false;

        $this->setEmail($email);
    }

    public function setEmail($email) {
        $this->data->email = $email;
    }

    public function getEmail() {
        return $this->data->email;
    }

    public function save() {
        $user = static::getByUsername($this->username);
        if($user->hasRow()) {
            throw new UserException(sprintf('The username %s already exists', $this->data->username), static::ERROR_TYPE_EXISTS);
        }
        parent::save();
    }

    public function updateData() {

        if($this->data !== null) {

            $userDataClass = static::getUserDataClass();
            $currentFields = $userDataClass::getByUserId($this->id);

            $cf = array();
            foreach($currentFields as $field) {
                $cf[strtolower($field->key)] = $field;
            }

            if(count($this->data->getData())) {
                foreach($this->data->getData() as $key=>$value) {

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
                            $cf[$key]->update();
                            unset($cf[$key]);
                        }
                    } else {
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
        $data = $class::getByUserId($this->id);
        if($data->hasRows()) {
            foreach($data->getRows() as $d) {
                $this->setDataValue($d->key, $d->value);
            }
        }
    }

    public function update() {
        return parent::update();
    }

    public function delete() {
        //\Pecee\Model\User\UserData::RemoveAll($this->id);
        $this->deleted = true;
        return parent::update();
    }


    public static function isLoggedIn($force = false) {
        if($force === true) {
            $user = static::getFromCookie(true);
            if($user !== null && $user->hasRow()) {
                return true;
            }
            return false;
        }
        return (Cookie::exists(static::COOKIE_NAME) && static::getFromCookie() !== null);
    }

    public function signOut() {
        if(Cookie::exists(static::COOKIE_NAME)) {
            Cookie::delete(static::COOKIE_NAME);
        }
    }

    public function exist() {
        return $this->scalar('SELECT u.`username` FROM {table} u WHERE u.`username` = %s && u.`deleted` = 0 LIMIT 1', $this->username);
    }

    public function registerActivity() {
        if($this->isLoggedIn()) {
            static::nonQuery('UPDATE {table} SET `last_activity` = NOW() WHERE `id` = %s', $this->id);
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
                    static::$instance = static::getById($user[0]);
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

    public static function get($query = null, $adminLevel = null, $deleted = null, $order = null, $rows = null, $page = null) {
        $order = (is_null($order) || !in_array($order, static::$ORDERS)) ? static::ORDER_ID_DESC : $order;

        $where = array('1=1');

        if($adminLevel !== null) {
            $where[] = PdoHelper::formatQuery('u.`admin_level` = %s', array($adminLevel));
        }
        if($deleted !== null) {
            $where[] = PdoHelper::formatQuery('u.`deleted` = %s', array($deleted));
        }
        if($query !== null) {
            $userData = static::getUserDataClass();
            $where[]='(`username` LIKE \'%%' . PdoHelper::escape($query).'%%\' OR (SELECT `' .  $userData::USER_IDENTIFIER_KEY . '` FROM `'.$userData.'` WHERE `'. $userData::USER_IDENTIFIER_KEY .'` = u.`id` && `value` LIKE \'%%'.PdoHelper::escape($query).'%%\' LIMIT 1))';
        }
        return static::fetchPage('SELECT u.* FROM {table} u WHERE ' . join(' && ', $where) . ' ORDER BY '.$order, $rows, $page);
    }

    /**
     * Get user by user id.
     * @param int $id
     * @return static
     */
    public static function getById($id) {
        return static::fetchOne('SELECT u.* FROM {table} u WHERE u.`id` = %s', array($id));
    }

    public static function getByIds(array $ids) {
        return static::fetchAll('SELECT u.* FROM {table} u WHERE u.`id` IN ('.PdoHelper::joinArray($ids).')' );
    }

    public static function getByUsername($username) {
        return static::fetchOne('SELECT u.* FROM {table} u WHERE u.`username` = %s && u.`deleted` = 0', $username);
    }

    public static function getByKeyValue($key, $value) {
        $userDataClass = static::getUserDataClass();
        return static::fetchOne('SELECT u.* FROM {table} u JOIN `'. $userDataClass .'` ud ON(ud.`'. $userDataClass::USER_IDENTIFIER_KEY .'` = u.`id`) WHERE ud.`key` = %s && ud.`value` = %s && u.`deleted` = 0', $key, $value);
    }

    public function auth() {
        return static::authenticate($this->username, $this->password, false);
    }

    public static function authenticate($username, $password, $remember = false) {
        static::onLoginStart($username, $password);
        $user = static::fetchOne('SELECT u.* FROM {table} u WHERE u.`deleted` = 0 && u.`username` = %s', $username);
        if(!$user->hasRows()) {
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

    protected static function onLoginStart($username, $password) {
        if(UserBadLogin::checkBadLogin($username)) {
            throw new UserException('User has been banned', static::ERROR_TYPE_BANNED);
        }
    }
}