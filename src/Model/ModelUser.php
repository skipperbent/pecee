<?php
namespace Pecee\Model;
use Pecee\Cookie;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\DB\PdoHelper;
use Pecee\Mcrypt;
use Pecee\Model\User\UserBadLogin;
use Pecee\Model\User\UserData;
use Pecee\Model\User\UserException;

class ModelUser extends ModelData {
	// Errors
	const ERROR_TYPE_BANNED = 0x1;
	const ERROR_TYPE_INVALID_LOGIN = 0x2;
	const ERROR_TYPE_EXISTS = 0x3;

	const ORDER_ID_DESC = 'u.`id` DESC';
	const ORDER_ID_ASC = 'u.`id` ASC';
	const ORDER_LASTACTIVITY_ASC = 'u.`last_activity` DESC';
	const ORDER_LASTACTIVITY_DESC = 'u.`last_activity` ASC';

	const TICKET_AUTH_KEY = 'TicketUserLoginKey';

	protected static $instance;

	public static $ORDERS = array(self::ORDER_ID_ASC, self::ORDER_ID_DESC, self::ORDER_LASTACTIVITY_ASC, self::ORDER_LASTACTIVITY_DESC);

	public function __construct($username = null, $password = null, $email = null) {

        $table = new DBTable('user');
        $table->column('id')->bigint()->primary()->increment();
        $table->column('username')->string(300)->index();
        $table->column('password')->string(32)->index();
        $table->column('last_activity')->datetime()->nullable()->index();
        $table->column('admin_level')->integer(1)->nullable()->index();
        $table->column('deleted')->bool()->index();

		parent::__construct($table);

        $this->username = $username;
        $this->password = md5($password);
        $this->admin_level = 0;
        $this->last_activity = Date::toDateTime();
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
		$user = self::getByUsername($this->username);
		if($user->hasRow()) {
			throw new UserException(sprintf('The username %s already exists', $this->data->username), self::ERROR_TYPE_EXISTS);
		}
		parent::save();
	}

	public function updateData() {
		if($this->data) {
			/* Remove all fields */
			UserData::removeAll($this->id);
			foreach($this->data->getData() as $key=>$value) {
				$data=new UserData($this->id, $key, $value);
				$data->save();
			}
		}
	}

	protected function fetchData() {
		$data = UserData::getById($this->id);
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
		//\Pecee\Model\User\UserData::RemoveAll($this->id);
		$this->deleted = true;
		return parent::update();
	}


	public static function isLoggedIn() {
		return Cookie::exists('ticket');
	}

	public function signOut() {
		if(Cookie::exists('ticket')) {
			Cookie::delete('ticket');
		}
	}

	public function exist() {
		return $this->scalar('SELECT u.`username` FROM {table} u WHERE u.`username` = %s && u.`deleted` = 0 LIMIT 1', $this->username);
	}

	public function registerActivity() {
		if($this->IsLoggedIn()) {
			self::nonQuery('UPDATE {table} SET `last_activity` = NOW() WHERE `id` = %s', $this->id);
		}
	}

	public function trackBadLogin() {
        UserBadLogin::track($this->username);
	}

	protected static function checkBadLogin() {
        return UserBadLogin::checkBadLogin();
	}

	protected function resetBadLogin() {
        UserBadLogin::reset();
	}

	protected function signIn($cookieExp){
		$user = array($this->id, $this->password, md5(microtime()), $this->username, $this->admin_level);
		$ticket = Mcrypt::encrypt(join('|',$user), self::generateLoginKey() );
		Cookie::create('ticket', $ticket, $cookieExp);
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
	public static function current($setData=false) {
		if(!is_null(self::$instance)) {
			return self::$instance;
		}
		if(self::isLoggedIn()){
			$ticket = Cookie::get('ticket');
			if(trim($ticket) != ''){
				$ticket = Mcrypt::decrypt($ticket, self::generateLoginKey() );
				$user = explode('|', $ticket);
				if(is_array($user)) {
					if($setData) {
						self::$instance = self::getById($user[0]);
					} else {
						$obj=new static();
						$obj->setRow('id', $user[0]);
						$obj->setRow('password', $user[1]);
						$obj->setRow('username', $user[3]);
						$obj->setRow('admin_level', $user[4]);
						return $obj;
					}
				}
			}
		}
		return self::$instance;
	}

	protected static function generateLoginKey() {
		return substr(md5(md5(self::TICKET_AUTH_KEY)), 0, 15);
	}

	public static function get($keyword=null, $adminLevel=null, $deleted=null, $order=null, $rows=null, $page=null) {
		$order=(is_null($order) || !in_array($order, self::$ORDERS)) ? self::ORDER_ID_DESC : $order;
		$where=array('1=1');
		if(!is_null($adminLevel)) {
			$where[] = PdoHelper::formatQuery('u.`admin_level` = %s', array($adminLevel));
		}
		if(!is_null($deleted)) {
			$where[] = PdoHelper::formatQuery('u.`deleted` = %s', array($deleted));
		}
		if(!is_null($keyword)) {
			$where[]='`username` LIKE \'%%' . PdoHelper::escape($keyword).'%%\'';
		}
		return self::fetchPage('SELECT u.* FROM {table} u WHERE ' . join(' && ', $where) . ' ORDER BY '.$order, $rows, $page);
	}

	/**
	 * Get user by user id.
	 * @param int $id
	 * @return self
	 */
	public static function getById($id) {
		return self::fetchOne('SELECT u.* FROM {table} u WHERE u.`id` = %s', array($id));
	}

	public static function getByIds(array $ids) {
		return self::fetchAll('SELECT u.* FROM {table} u WHERE u.`id` IN ('.PdoHelper::joinArray($ids).')' );
	}

	public static function getByUsernameOrEmail($query, $rows = 10, $page = 0) {
		return self::fetchPage('SELECT u.* FROM {table} u JOIN `user_data` ud ON(ud.`id` = u.`id`) WHERE (ud.`key` = \'email\' && ud.`value` LIKE %s || u.`username` LIKE %s) && u.`deleted` = 0', $rows, $page, $query, $query);
	}

	public static function getByUsername($username) {
		return self::fetchOne('SELECT u.* FROM {table} u WHERE u.`username` = %s && u.`deleted` = 0', $username);
	}

	public static function getByEmail($email) {
		return self::fetchOne('SELECT u.* FROM {table} u JOIN `user_data` ud ON(ud.`id` = u.`id`) WHERE ud.`key` = \'email\' && ud.`value` = %s && u.`deleted` = 0', $email);
	}

	public function auth() {
		return self::authenticate($this->username, $this->password, false);
	}

	public static function authenticateByEmail($email, $password, $remember=false) {
		if(self::checkBadLogin()) {
			throw new UserException('User has been banned', self::ERROR_TYPE_BANNED);
		}
		$user = self::fetchOne('SELECT u.`id`, u.`username`, u.`password`, u.`admin_level` FROM {table} u JOIN `user_data` ud ON(ud.`id` = u.`id`) WHERE u.`deleted` = 0 && ud.`key` = \'email\' && ud.`value` = %s', $email);
		if(!$user->hasRows()) {
			throw new UserException('Invalid login', self::ERROR_TYPE_INVALID_LOGIN);
		}
		// Incorrect user login (track bad request).
		if(strtolower($user->getEmail()) != strtolower($email) || $user->password != md5($password) && $user->password != $password) {
			$user->trackBadLogin();
			throw new UserException('Invalid login', self::ERROR_TYPE_INVALID_LOGIN);
		}
		$user->resetBadLogin();
		$user->signIn(($remember) ? null : 0);
		return $user;
	}

	public static function authenticate($username, $password, $remember = false) {
		if(self::checkBadLogin()) {
			throw new UserException('User has been banned', self::ERROR_TYPE_BANNED);
		}
		$user = self::fetchOne('SELECT u.* FROM {table} u WHERE u.`deleted` = 0 && u.`username` = %s', $username);
		if(!$user->hasRows()) {
			throw new UserException('Invalid login', self::ERROR_TYPE_INVALID_LOGIN);
		}
		// Incorrect user login (track bad request).
		if(strtolower($user->username) != strtolower($username) || $user->password != md5($password) && $user->password != $password) {
			$user->trackBadLogin();
			throw new UserException('Invalid login', self::ERROR_TYPE_INVALID_LOGIN);
		}
		$user->resetBadLogin();
		$user->signIn(($remember) ? null : 0);
		return $user;
	}
}