<?php
namespace Pecee\Model;
use Pecee\Cookie;
use Pecee\Date;
use Pecee\DB\DB;
use Pecee\DB\DBTable;
use Pecee\Mcrypt;
use Pecee\Model\User\UserBadLogin;
use Pecee\Model\User\UserData;
use Pecee\Model\User\UserException;

class ModelUser extends ModelData {
	// Errors
	const ERROR_TYPE_BANNED = 'USER_ERROR_BANNED';
	const ERROR_TYPE_INVALID_USER = 'USER_ERROR_INVALID_USER';
	const ERROR_TYPE_INVALID_LOGIN = 'USER_ERROR_INVALID_LOGIN';
	const ERROR_TYPE_EXISTS = 'USER_ERROR_EXISTS';

	const ORDER_ID_DESC = 'u.`userId` DESC';
	const ORDER_ID_ASC = 'u.`userId` ASC';
	const ORDER_LASTACTIVITY_ASC = 'u.`lastActivity` DESC';
	const ORDER_LASTACTIVITY_DESC = 'u.`lastActivity` ASC';
	const TICKET_AUTH_KEY = 'TicketUserLoginKey';

	protected static $instance;

	public static $ORDERS = array(self::ORDER_ID_ASC, self::ORDER_ID_DESC, self::ORDER_LASTACTIVITY_ASC, self::ORDER_LASTACTIVITY_DESC);
	public function __construct($username = null, $password = null, $email = null) {

        $table = new DBTable('user');
        $table->column('userId')->bigint()->primary()->increment();
        $table->column('username')->string(300)->index();
        $table->column('password')->string(32)->index();
        $table->column('lastActivity')->datetime()->nullable()->index();
        $table->column('adminLevel')->integer(1)->nullable()->index();
        $table->column('deleted')->bool()->index();

		parent::__construct($table);

        $this->username = $username;
        $this->password = md5($password);
        $this->adminLevel = 0;
        $this->lastActivity = Date::ToDateTime();
        $this->deleted = false;

		$this->setEmail($email);
	}

	public function setEmail($email) {
		$this->data->email=$email;
	}

	public function getEmail() {
		return $this->data->email;
	}

	public function save() {
		$user = self::GetByUsername($this->username);
		if($user->hasRow()) {
			throw new UserException(sprintf('The username %s already exists', $this->data->username), self::ERROR_TYPE_EXISTS);
		}
		parent::save();
	}

	public function updateData() {
		if($this->data) {
			/* Remove all fields */
			UserData::RemoveAll($this->userId);
			foreach($this->data->getData() as $key=>$value) {
				$data=new UserData($this->userId, $key, $value);
				$data->save();
			}
		}
	}

	protected function fetchData() {
		$data = UserData::GetByUserID($this->userId);
		if($data->hasRows()) {
			foreach($data->getRows() as $d) {
				$this->setDataValue($d->getKey(), $d->getValue());
			}
		}
	}

	public function update() {
		return parent::update();
	}

	public function delete() {
		//\Pecee\Model\User\UserData::RemoveAll($this->UserID);
		$this->deleted = true;
		return parent::update();
	}


	public static function IsLoggedIn() {
		return Cookie::Exists('ticket');
	}

	public function signOut() {
		if(Cookie::Exists('ticket')) {
			Cookie::Delete('ticket');
		}
	}

	public function exist() {
		return $this->scalar('SELECT u.`username` FROM {table} u WHERE u.`username` = %s && u.`deleted` = 0 LIMIT 1', $this->username);
	}

	public function registerActivity() {
		if($this->IsLoggedIn()) {
			self::NonQuery('UPDATE {table} SET `lastActivity` = NOW() WHERE `userId` = %s', $this->userId);
		}
	}

	public function trackBadLogin() {
        UserBadLogin::Track($this->username);
	}

	protected static function CheckBadLogin() {
        return UserBadLogin::CheckBadLogin();
	}

	protected function resetBadLogin() {
        UserBadLogin::Reset();
	}

	protected function signIn($cookieExp){
		$user = array($this->userId, $this->password, md5(microtime()), $this->username, $this->adminLevel);
		$ticket = Mcrypt::Encrypt(join('|',$user), self::GenerateLoginKey() );
		Cookie::Create('ticket', $ticket, $cookieExp);
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

	/**
	 * Get current user
     * @param bool $setData
	 * @return self
	 */
	public static function Current($setData=false) {
		if(!is_null(self::$instance)) {
			return self::$instance;
		}
		if(self::IsLoggedIn()){
			$ticket = Cookie::Get('ticket');
			if(trim($ticket) != ''){
				$ticket = Mcrypt::Decrypt($ticket, self::GenerateLoginKey() );
				$user = explode('|', $ticket);
				if(is_array($user)) {
					if($setData) {
						self::$instance = self::GetByUserID($user[0]);
					} else {
						$caller=get_called_class();
						$obj=new $caller();
						$obj->setRow('userId', $user[0]);
						$obj->setRow('password', $user[1]);
						$obj->setRow('username', $user[3]);
						$obj->setRow('adminLevel', $user[4]);
						return $obj;
					}
				}
			}
		}
		return self::$instance;
	}

	protected static function GenerateLoginKey() {
		return substr(md5(md5(self::TICKET_AUTH_KEY)), 0, 15);
	}

	public static function Get($keyword=null, $adminLevel=null, $deleted=null, $order=null, $rows=null, $page=null) {
		$order=(is_null($order) || !in_array($order, self::$ORDERS)) ? self::ORDER_ID_DESC : $order;
		$where=array('1=1');
		if(!is_null($adminLevel)) {
			$where[]=DB::FormatQuery('u.`adminLevel` = %s', array($adminLevel));
		}
		if(!is_null($deleted)) {
			$where[]=DB::FormatQuery('u.`deleted` = %s', array($deleted));
		}
		if(!is_null($keyword)) {
			$where[]='`username` LIKE \'%%'.DB::Escape($keyword).'%%\'';
		}
		return self::FetchPage('SELECT u.* FROM {table} u WHERE ' . join(' && ', $where) . ' ORDER BY '.$order, $rows, $page);
	}

	/**
	 * Get user by user id.
	 * @param int $userId
	 * @return self
	 */
	public static function GetByUserID($userId) {
		return self::FetchOne('SELECT u.* FROM {table} u WHERE u.`userId` = %s', array($userId));
	}

	public static function GetByUserIDs(array $userIds) {
		return self::FetchAll('SELECT u.* FROM {table} u WHERE u.`userId` IN ('.DB::JoinArray($userIds).')' );
	}

	public static function GetByUsernameOrEmail($query, $rows = 10, $page = 0) {
		return self::FetchPage('SELECT u.* FROM {table} u JOIN `user_data` ud ON(ud.`userId` = u.`userId`) WHERE (ud.`key` = \'email\' && ud.`value` LIKE %s || u.`username` LIKE %s) && u.`deleted` = 0', $rows, $page, $query, $query);
	}

	public static function GetByUsername($username) {
		return self::FetchOne('SELECT u.* FROM {table} u WHERE u.`username` = %s && u.`deleted` = 0', $username);
	}

	public static function GetByEmail($email) {
		return self::FetchOne('SELECT u.* FROM {table} u JOIN `user_data` ud ON(ud.`userID` = u.`userId`) WHERE ud.`key` = \'email\' && ud.`value` = %s && u.`deleted` = 0', $email);
	}

	public function auth() {
		return self::Authenticate($this->username, $this->password, false);
	}

	public static function AuthenticateByEmail($email, $password, $remember=false) {
		if(self::CheckBadLogin()) {
			return self::ERROR_TYPE_BANNED;
		}
		$user = self::FetchOne('SELECT u.`userId`, u.`username`, u.`password`, u.`adminLevel` FROM {table} u JOIN `user_data` ud ON(ud.`userId` = u.`userId`) WHERE u.`deleted` = 0 && ud.`key` = \'email\' && ud.`value` = %s', $email);
		if(!$user->hasRows()) {
			return self::ERROR_TYPE_INVALID_USER;
		}
		// Incorrect user login (track bad request).
		if(strtolower($user->getEmail()) != strtolower($email) || $user->password != md5($password) && $user->password != $password) {
			$user->trackBadLogin();
			return self::ERROR_TYPE_INVALID_LOGIN;
		}
		$user->resetBadLogin();
		$user->signIn(($remember) ? null : 0);
		return $user;
	}

	public static function Authenticate($username, $password, $remember = false) {
		if(self::CheckBadLogin()) {
			return self::ERROR_TYPE_BANNED;
		}
		$user = self::FetchOne('SELECT u.* FROM {table} u WHERE u.`deleted` = 0 && u.`username` = %s', $username);
		if(!$user->hasRows()) {
			return self::ERROR_TYPE_INVALID_USER;
		}
		// Incorrect user login (track bad request).
		if(strtolower($user->username) != strtolower($username) || $user->password != md5($password) && $user->password != $password) {
			$user->trackBadLogin();
			return self::ERROR_TYPE_INVALID_LOGIN;
		}
		$user->resetBadLogin();
		$user->signIn(($remember) ? null : 0);
		return $user;
	}
}