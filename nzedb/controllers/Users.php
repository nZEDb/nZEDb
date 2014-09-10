<?php

use nzedb\db\Settings;
use nzedb\utility;

/**
 * Class Users
 */
class Users
{
	const ERR_SIGNUP_BADUNAME = -1;
	const ERR_SIGNUP_BADPASS = -2;
	const ERR_SIGNUP_BADEMAIL = -3;
	const ERR_SIGNUP_UNAMEINUSE = -4;
	const ERR_SIGNUP_EMAILINUSE = -5;
	const ERR_SIGNUP_BADINVITECODE = -6;
	const FAILURE = 0;
	const SUCCESS = 1;

	const ROLE_GUEST = 0;
	const ROLE_USER = 1;
	const ROLE_ADMIN = 2;
	const ROLE_DISABLED = 3;
	const ROLE_MODERATOR = 4;

	const DEFAULT_INVITES = 1;
	const DEFAULT_INVITE_EXPIRY_DAYS = 7;

	const SALTLEN = 4;
	const SHA1LEN = 40;

	/**
	 * Users select queue type.
	 */
	const QUEUE_NONE    = 0;
	const QUEUE_SABNZBD = 1;
	const QUEUE_NZBGET  = 2;

	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var int
	 */
	public $password_hash_cost;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());

		// password_hash functions are available on PHP 5.5.0 or higher, use password_compat for forward compatibility on older versions.
		if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
			require_once(nZEDb_LIBS . 'password_compat' . DS . 'lib' . DS . 'password.php');
		}
		$this->password_hash_cost = (defined('nZEDb_PASSWORD_HASH_COST') ? nZEDb_PASSWORD_HASH_COST : 11);
	}

	/**
	 * Get all rows from the users table.
	 *
	 * @return array
	 */
	public function get()
	{
		return $this->pdo->query("SELECT * FROM users");
	}

	/**
	 * Get the users selected theme.
	 *
	 * @param string|int $userID The id of the user.
	 *
	 * @return array|bool The users selected theme.
	 */
	public function getStyle($userID)
	{
		$row = $this->pdo->queryOneRow(sprintf("SELECT style FROM users WHERE id = %d", $userID));
		return ($row === false ? 'None' : $row['style']);
	}

	/**
	 * Delete a user and all it's corresponding data.
	 *
	 * @param int $userID ID of the user.
	 */
	public function delete($userID)
	{
		$this->delCartForUser($userID);
		$this->delUserCategoryExclusions($userID);
		(new \ReleaseComments($this->pdo))->deleteCommentsForUser($userID);
		(new \UserMovies(['Settings' => $this->pdo]))->delMovieForUser($userID);
		(new \UserSeries(['Settings' => $this->pdo]))->delShowForUser($userID);
		(new \Forum(['Settings' => $this->pdo]))->deleteUser($userID);

		$this->pdo->queryExec(sprintf("DELETE FROM users WHERE id = %d", $userID));
	}

	/**
	 * Get all users / extra data from other tables.
	 *
	 * @param        $start
	 * @param        $offset
	 * @param        $orderBy
	 * @param string $userName
	 * @param string $email
	 * @param string $host
	 * @param string $role
	 * @param bool   $apiRequests
	 *
	 * @return array
	 */
	public function getRange($start, $offset, $orderBy, $userName = '', $email = '', $host = '', $role = '', $apiRequests = false)
	{
		if ($apiRequests) {
			$this->clearApiRequests(false);
			$query = ("
				SELECT users.*, userroles.name AS rolename, COUNT(userrequests.id) AS apirequests
				FROM users
				INNER JOIN userroles ON userroles.id = users.role
				LEFT JOIN userrequests ON userrequests.user_id = users.id
				WHERE users.id != 0 %s %s %s %s
				AND email != 'sharing@nZEDb.com'
				GROUP BY users.id
				ORDER BY %s %s %s"
			);
		} else {
			$query = ("
				SELECT users.*, userroles.name AS rolename
				FROM users
				INNER JOIN userroles ON userroles.id = users.role
				WHERE 1=1 %s %s %s %s
				ORDER BY %s %s %s"
			);
		}

		$order = $this->getBrowseOrder($orderBy);

		return $this->pdo->query(
			sprintf(
				$query,
				($userName != '' ? ('AND users.username ' . $this->pdo->likeString($userName)) : ''),
				($email != '' ? ('AND users.email ' . $this->pdo->likeString($email)) : ''),
				($host != '' ? ('AND users.host ' . $this->pdo->likeString($host)) : ''),
				($role != '' ? ('AND users.role = ' . $role) : ''),
				$order[0],
				$order[1],
				($start === false ? '' : ('LIMIT ' . $offset . ' OFFSET ' . $start))
			)
		);
	}

	/**
	 * Get sort types for sorting users on the web page user list.
	 *
	 * @param $orderBy
	 *
	 * @return array
	 */
	public function getBrowseOrder($orderBy)
	{
		$order = ($orderBy == '' ? 'username_desc' : $orderBy);
		$orderArr = explode("_", $order);
		switch ($orderArr[0]) {
			case 'username':
				$orderField = 'username';
				break;
			case 'email':
				$orderField = 'email';
				break;
			case 'host':
				$orderField = 'host';
				break;
			case 'createddate':
				$orderField = 'createddate';
				break;
			case 'lastlogin':
				$orderField = 'lastlogin';
				break;
			case 'apiaccess':
				$orderField = 'apiaccess';
				break;
			case 'grabs':
				$orderField = 'grabs';
				break;
			case 'role':
				$orderField = 'role';
				break;
			default:
				$orderField = 'username';
				break;
		}
		$orderSort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderField, $orderSort);
	}

	/**
	 * Get count of all users.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$res = $this->pdo->queryOneRow("SELECT COUNT(id) AS num FROM users WHERE email != 'sharing@nZEDb.com'");
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Add a new user.
	 *
	 * @param     $userName
	 * @param     $firstName
	 * @param     $lastName
	 * @param     $password
	 * @param     $email
	 * @param     $role
	 * @param     $host
	 * @param int $invites
	 * @param int $invitedBy
	 *
	 * @return bool|int
	 */
	public function add($userName, $firstName, $lastName, $password, $email, $role, $host, $invites = \Users::DEFAULT_INVITES, $invitedBy = 0)
	{
		$password = $this->hashPassword($password);
		if (!$password) {
			return false;
		}
		return $this->pdo->queryInsert(
			sprintf("
				INSERT INTO users (username, password, email, role, createddate, host, rsstoken,
					invites, invitedby, userseed, firstname, lastname)
				VALUES (%s, %s, LOWER(%s), %d, NOW(), %s, MD5(%s), %d, %s, MD5(%s), %s, %s)",
				$this->pdo->escapeString($userName),
				$this->pdo->escapeString((string)$password),
				$this->pdo->escapeString($email),
				$role,
				$this->pdo->escapeString(($this->pdo->getSetting('storeuserips') == 1 ? $host : '')),
				$this->pdo->escapeString(uniqid()),
				$invites,
				($invitedBy == 0 ? 'NULL' : $invitedBy),
				$this->pdo->escapeString($this->pdo->uuid()),
				$this->pdo->escapeString($firstName),
				$this->pdo->escapeString($lastName)
			)
		);
	}

	/**
	 * Update an existing user.
	 *
	 * @param        $id
	 * @param        $userName
	 * @param        $firstName
	 * @param        $lastName
	 * @param        $email
	 * @param        $grabs
	 * @param        $role
	 * @param        $invites
	 * @param        $movieView
	 * @param        $xxxView
	 * @param        $musicView
	 * @param        $consoleView
	 * @param        $gameView
	 * @param        $bookView
	 * @param bool   $cp_url
	 * @param bool   $cp_api
	 * @param string $style
	 * @param string $queueType
	 * @param string $nzbGetURL
	 * @param string $nzbGetUsername
	 * @param string $nzbGetPassword
	 * @param string $sabURL
	 * @param string $sabApiKey
	 * @param string $sabPriority
	 * @param string $sabApiKeyType
	 *
	 * @return int
	 */
	public function update($id, $userName, $firstName, $lastName, $email, $grabs, $role, $invites,
		$movieView, $xxxView, $musicView, $consoleView, $gameView, $bookView,
		$cp_url = false, $cp_api = false, $style = 'None', $queueType = '',
		$nzbGetURL = '', $nzbGetUsername = '', $nzbGetPassword = '',
		$sabURL = '', $sabApiKey = '', $sabPriority = '', $sabApiKeyType = '')
	{

		$userName = trim($userName);
		$email = trim($email);

		if (!$this->isValidUsername($userName)) {
			return \Users::ERR_SIGNUP_BADUNAME;
		}

		$check = $this->getByUsername($userName);
		if ($check !== false && $check['id'] != $id) {
			return \Users::ERR_SIGNUP_UNAMEINUSE;
		}

		if (!$this->isValidEmail($email)) {
			return \Users::ERR_SIGNUP_BADEMAIL;
		}

		$check = $this->getByEmail($email);
		if ($check !== false && $check['id'] != $id) {
			return \Users::ERR_SIGNUP_EMAILINUSE;
		}

		$sql = array();

		$sql[] = sprintf('username = %s', $this->pdo->escapeString($userName));
		$sql[] = sprintf('email = %s', $this->pdo->escapeString($email));

		$firstName = trim($firstName);
		$lastName = trim($lastName);
		if ($firstName !== false) {
			$sql[] = sprintf('firstname = %s', $this->pdo->escapeString($firstName));
		}
		if ($lastName !== false) {
			$sql[] = sprintf('lastname = %s', $this->pdo->escapeString($lastName));
		}

		$sql[] = sprintf('grabs = %d', $grabs);
		$sql[] = sprintf('role = %d', $role);
		$sql[] = sprintf('invites = %d', $invites);
		$sql[] = sprintf('movieview = %d', $movieView);
		$sql[] = sprintf('xxxview = %d', $xxxView);
		$sql[] = sprintf('musicview = %d', $musicView);
		$sql[] = sprintf('consoleview = %d', $consoleView);
		$sql[] = sprintf('gameview = %d', $gameView);
		$sql[] = sprintf('bookview = %d', $bookView);
		$sql[] = sprintf('style = %s', $this->pdo->escapeString($style));
		if ($queueType !== '') {
			$sql[] = sprintf('queuetype = %d', $queueType);
		}

		if ($nzbGetURL !== '') {
			$sql[] = sprintf('nzbgeturl = %s', $this->pdo->escapeString($nzbGetURL));
		}

		$sql[] = sprintf('nzbgetusername = %s', $this->pdo->escapeString($nzbGetUsername));
		$sql[] = sprintf('nzbgetpassword = %s', $this->pdo->escapeString($nzbGetPassword));

		if ($sabURL !== '') {
			$sql[] = sprintf('saburl = %s', $this->pdo->escapeString($sabURL));
		}
		if ($sabApiKey !== '') {
			$sql[] = sprintf('sabapikey = %s', $this->pdo->escapeString($sabApiKey));
		}
		if ($sabPriority !== '') {
			$sql[] = sprintf('sabpriority = %d', $sabPriority);
		}
		if ($sabApiKeyType !== '') {
			$sql[] = sprintf('sabapikeytype = %d', $sabApiKeyType);
		}

		if ($cp_url !== false) {
			$sql[] = sprintf('cp_url = %s', $this->pdo->escapeString($cp_url));
		}
		if ($cp_api !== false) {
			$sql[] = sprintf('cp_api = %s', $this->pdo->escapeString($cp_api));
		}
		$this->pdo->queryExec(sprintf("UPDATE users SET %s WHERE id = %d", implode(', ', $sql), $id));

		return \Users::SUCCESS;
	}

	/**
	 * Change the user's API key.
	 *
	 * @param int $userID ID of the user.
	 *
	 * @return bool|PDOStatement
	 */
	public function updateRssKey($userID)
	{
		return $this->pdo->queryExec(
			sprintf("UPDATE users SET rsstoken = MD5(%s) WHERE id = %d", $this->pdo->escapeString(uniqid()), $userID)
		);
	}

	/**
	 * @param int    $userID
	 * @param string $GUID
	 *
	 * @return int
	 */
	public function updatePassResetGuid($userID, $GUID)
	{
		$this->pdo->queryExec(sprintf("UPDATE users SET resetguid = %s WHERE id = %d", $this->pdo->escapeString($GUID), $userID));
		return \Users::SUCCESS;
	}

	/**
	 * Update a user's password.
	 *
	 * @param int    $userID   ID of the user.
	 * @param string $password New password.
	 *
	 * @return int
	 */
	public function updatePassword($userID, $password)
	{
		$password = $this->hashPassword($password);
		if (!$password) {
			return \Users::FAILURE;
		}
		$this->pdo->queryExec(
			sprintf(
				"UPDATE users SET password = %s, userseed = MD5(%s) WHERE id = %d",
				$this->pdo->escapeString((string)$password),
				$this->pdo->escapeString($this->pdo->uuid()),
				$userID
			)
		);
		return \Users::SUCCESS;
	}

	/**
	 * Get user info by their email.
	 *
	 * @param string $email
	 *
	 * @return array|bool
	 */
	public function getByEmail($email)
	{
		return $this->pdo->queryOneRow(
			sprintf("SELECT * FROM users WHERE LOWER(email) = LOWER(%s) ", $this->pdo->escapeString($email))
		);
	}

	/**
	 * @param string $GUID
	 *
	 * @return array|bool
	 */
	public function getByPassResetGuid($GUID)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT * FROM users WHERE LOWER(resetguid) = LOWER(%s) ",
				$this->pdo->escapeString($GUID)
			)
		);
	}

	/**
	 * Get all info on a user by their username.
	 *
	 * @param string $userName Username of the user.
	 *
	 * @return array|bool
	 */
	public function getByUsername($userName)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT * FROM users WHERE LOWER(username) = LOWER(%s)",
				$this->pdo->escapeString($userName)
			)
		);
	}

	/**
	 * When a user downloads a NZB, increment their grab count.
	 *
	 * @param int $userID    ID of the user.
	 * @param int $increment How much should we increment the count.
	 */
	public function incrementGrabs($userID, $increment = 1)
	{
		$this->pdo->queryExec(
			sprintf(
				"UPDATE users SET grabs = grabs + %d WHERE id = %d ",
				$increment,
				$userID
			)
		);
	}

	/**
	 * Get user info and all associated info from other tables for a single user by ID.
	 *
	 * @param int $userID ID of the user.
	 *
	 * @return array|bool
	 */
	public function getById($userID)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT users.*, userroles.name AS rolename, userroles.canpreview,
					userroles.apirequests, userroles.downloadrequests, NOW() AS now
				FROM users
				INNER JOIN userroles ON userroles.id = users.role
				WHERE users.id = %d",
				$userID
			)
		);
	}

	/**
	 * Check if the user is in the database, and if their API key is good, return user data if so.
	 *
	 * @param int    $userID   ID of the user.
	 * @param string $rssToken API key.
	 *
	 * @return bool|array
	 */
	public function getByIdAndRssToken($userID, $rssToken)
	{
		$user = $this->getById($userID);
		if ($user === false) {
			return false;
		}

		return ($user['rsstoken'] != $rssToken ? false : $user);
	}

	/**
	 * Get all user info and associated info from other tables using their API key.
	 * @param string $rssToken API key.
	 *
	 * @return array|bool
	 */
	public function getByRssToken($rssToken)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT users.*, userroles.apirequests, userroles.downloadrequests, NOW() AS now
				FROM users
				INNER JOIN userroles ON userroles.id = users.role
				WHERE LOWER(users.rsstoken) =
				LOWER(%s)",
				$this->pdo->escapeString($rssToken)
			)
		);
	}

	/**
	 * Get valid list of user sorting methods.
	 *
	 * @return array
	 */
	public function getBrowseOrdering()
	{
		return array(
			'username_asc',
			'username_desc',
			'email_asc',
			'email_desc',
			'host_asc',
			'host_desc',
			'createddate_asc',
			'createddate_desc',
			'lastlogin_asc',
			'lastlogin_desc',
			'apiaccess_asc',
			'apiaccess_desc',
			'grabs_asc',
			'grabs_desc',
			'role_asc',
			'role_desc'
		);
	}

	/**
	 * When a user is registering, verify if their username meets certain criteria.
	 * It must be at least three characters and alphanumeric.
	 *
	 * @param string $userName
	 *
	 * @return bool
	 */
	public function isValidUsername($userName)
	{
		return ((ctype_alnum($userName) && strlen($userName) > 2) ? true : false);
	}

	/**
	 * When a user is changing their password of registering, verify their password meets certain criteria.
	 * It must be at least 6 characters.
	 *
	 * @param $password
	 *
	 * @return bool
	 */
	public function isValidPassword($password)
	{
		return (strlen($password) > 5);
	}

	/**
	 * Check if the user is disabled.
	 *
	 * @param string $userName Name of the user.
	 *
	 * @return bool
	 */
	public function isDisabled($userName)
	{
		$role = $this->pdo->queryOneRow(
			sprintf(
				"SELECT role AS role FROM users WHERE username = %s",
				$this->pdo->escapeString($userName)
			)
		);
		return ($role === false ? false : $role['role'] == \Users::ROLE_DISABLED);
	}

	/**
	 * When a user is registering or updating their profile, check if the email is valid.
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function isValidEmail($email)
	{
		return (bool)preg_match('/^([\w\+-]+)(\.[\w\+-]+)*@([a-z0-9-]+\.)+[a-z]{2,6}$/i', $email);
	}

	/**
	 * Check if a URL is valid.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public function isValidUrl($url)
	{
		return (bool)preg_match('/^(http|https|ftp):\/\/([A-Z0-9][\w-]*(?:\.[A-Z0-9][\w-]*)+):?(\d+)?\/?/i', $url);
	}

	/**
	 * Create a random username.
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	public function generateUsername($email)
	{
		$string = '';
		if (preg_match('/[A-Za-z0-9]+/', $email, $matches)) {
			$string = $matches[0];
		}

		return "u" . substr(md5(uniqid()), 0, 7) . $string;
	}

	/**
	 * Create a password.
	 *
	 * @return string
	 */
	public function generatePassword()
	{
		return substr(md5(uniqid()), 0, 8);
	}

	/**
	 * Register a new user.
	 *
	 * @param        $userName
	 * @param        $firstName
	 * @param        $lastName
	 * @param        $password
	 * @param        $email
	 * @param        $host
	 * @param int    $role
	 * @param int    $invites
	 * @param string $inviteCode
	 * @param bool   $forceInviteMode
	 *
	 * @return bool|int
	 */
	public function signUp(
		$userName, $firstName, $lastName, $password, $email, $host, $role = \Users::ROLE_USER,
		$invites = \Users::DEFAULT_INVITES, $inviteCode = '', $forceInviteMode = false
	) {
		$userName = trim($userName);
		if (!$this->isValidUsername($userName)) {
			return \Users::ERR_SIGNUP_BADUNAME;
		}

		$password = trim($password);
		if (!$this->isValidPassword($password)) {
			return \Users::ERR_SIGNUP_BADPASS;
		}

		$email = trim($email);
		if (!$this->isValidEmail($email)) {
			return \Users::ERR_SIGNUP_BADEMAIL;
		}

		$res = $this->getByUsername($userName);
		if ($res) {
			return \Users::ERR_SIGNUP_UNAMEINUSE;
		}

		$res1 = $this->getByEmail($email);
		if ($res1) {
			return \Users::ERR_SIGNUP_EMAILINUSE;
		}

		// Make sure this is the last check, as if a further validation check failed, the invite would still have been used up.
		$invitedBy = 0;
		if (($this->pdo->getSetting('registerstatus') == Settings::REGISTER_STATUS_INVITE) && !$forceInviteMode) {
			if ($inviteCode == '') {
				return \Users::ERR_SIGNUP_BADINVITECODE;
			}

			$invitedBy = $this->checkAndUseInvite($inviteCode);
			if ($invitedBy < 0) {
				return \Users::ERR_SIGNUP_BADINVITECODE;
			}
		}

		return $this->add($userName, trim($firstName), trim($lastName), $password, $email, $role, $host, $invites, $invitedBy);
	}

	/**
	 * Create a random key up to specified length.
	 *
	 * @param int $maxLength
	 *
	 * @return string
	 */
	public function randomKey($maxLength)
	{
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$characterLength = (strlen($characters) - 1);
		$randomKey = '';
		for ($i = 0; $i < $maxLength; $i++) {
			$randomKey .= $characters[mt_rand(0, $characterLength)];
		}
		return $randomKey;
	}

	/**
	 * SHA1 a string.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function hashSHA1($string)
	{
		return sha1($string);
	}

	/**
	 * Hash a password using crypt.
	 *
	 * @param string $password
	 *
	 * @return string|bool
	 */
	public function hashPassword($password)
	{
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->password_hash_cost]);
	}

	/**
	 * Verify a password against a hash.
	 *
	 * Automatically update the hash if it needs to be.
	 *
	 * @param string $password Password to check against hash.
	 * @param string $hash     Hash to check against password.
	 * @param int    $userID   ID of the user.
	 *
	 * @return bool
	 */
	public function checkPassword($password, $hash, $userID = -1)
	{
		if (password_verify($password, $hash) === false) {
			return false;
		}

		// Update the hash if it needs to be.
		if (is_numeric($userID) && $userID > 0 && password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => $this->password_hash_cost])) {
			$hash = $this->hashPassword($password);

			if ($hash !== false) {
				$this->pdo->queryExec(
					sprintf(
						'UPDATE users SET password = %s WHERE id = %d',
						$this->pdo->escapeString((string)$hash),
						$userID
					)
				);
			}
		}
		return true;
	}

	/**
	 * Verify if the user is logged in.
	 *
	 * @return bool
	 */
	public function isLoggedIn()
	{
		if (isset($_SESSION['uid'])) {
			return true;
		} else if (isset($_COOKIE['uid']) && isset($_COOKIE['idh'])) {
			$u = $this->getById($_COOKIE['uid']);

			if (($_COOKIE['idh'] == $this->hashSHA1($u["userseed"] . $_COOKIE['uid'])) && ($u["role"] != \Users::ROLE_DISABLED)) {
				$this->login($_COOKIE['uid'], $_SERVER['REMOTE_ADDR']);
			}
		}
		return isset($_SESSION['uid']);
	}

	/**
	 * Return the User ID of the user.
	 *
	 * @return int
	 */
	public function currentUserId()
	{
		return (isset($_SESSION['uid']) ? $_SESSION['uid'] : -1);
	}

	/**
	 * Logout the user, destroying his cookies and session.
	 */
	public function logout()
	{
		session_unset();
		session_destroy();
		setcookie('uid', '', (time() - 2592000));
		setcookie('idh', '', (time() - 2592000));
	}

	/**
	 * Log in a user.
	 *
	 * @param int    $userID   ID of the user.
	 * @param string $host
	 * @param string $remember Save the user in cookies to keep them logged in.
	 */
	public function login($userID, $host = '', $remember = '')
	{
		$_SESSION['uid'] = $userID;

		if ($this->pdo->getSetting('storeuserips') != 1) {
			$host = '';
		}

		$this->updateSiteAccessed($userID, $host);

		if ($remember == 1) {
			$this->setCookies($userID);
		}
	}

	/**
	 * When a user logs in, update the last time they logged in.
	 *
	 * @param int    $userID ID of the user.
	 * @param string $host
	 */
	public function updateSiteAccessed($userID, $host = '')
	{
		$this->pdo->queryExec(
			sprintf(
				"UPDATE users SET lastlogin = NOW() %s WHERE id = %d",
				($host == '' ? '' : (', host = ' . $this->pdo->escapeString($host))),
				$userID
			)
		);
	}

	/**
	 * When a user accesses the API, update the access time.
	 *
	 * @param int $userID
	 */
	public function updateApiAccessed($userID)
	{
		$this->pdo->queryExec(sprintf("UPDATE users SET apiaccess = NOW() WHERE id = %d", $userID));
	}

	/**
	 * Set up cookies for a user.
	 *
	 * @param int $userID
	 */
	public function setCookies($userID)
	{
		$user = $this->getById($userID);
		$secure_cookie = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? '1' : '0');
		setcookie('uid', $userID, (time() + 2592000), '/', '', $secure_cookie, 'true');
		setcookie('idh', ($this->hashSHA1($user['userseed'] . $userID)), (time() + 2592000), '/', '', $secure_cookie, 'true');
	}

	/**
	 * Add a release to the user's cart.
	 *
	 * @param int $userID
	 * @param int $releaseID
	 *
	 * @return bool|int
	 */
	public function addCart($userID, $releaseID)
	{
		return $this->pdo->queryInsert(
			sprintf(
				"INSERT INTO usercart (user_id, releaseid, createddate) VALUES (%d, %d, NOW())",
				$userID,
				$releaseID
			)
		);
	}

	/**
	 * Get all items from the user's cart.
	 *
	 * @param int $userID ID of the user.
	 *
	 * @return array
	 */
	public function getCart($userID)
	{
		return $this->pdo->query(
			sprintf("
				SELECT usercart.*, releases.searchname, releases.guid
				FROM usercart
				INNER JOIN releases ON releases.id = usercart.releaseid
				WHERE user_id = %d",
				$userID
			)
		);
	}

	/**
	 * Delete items from the users cart.
	 *
	 * @param array $ids    List of items to delete.
	 * @param int   $userID ID of the user.
	 *
	 * @return bool
	 */
	public function delCart($ids, $userID)
	{
		if (!is_array($ids)) {
			return false;
		}

		$del = array();
		foreach ($ids as $id) {
			if (is_numeric($id)) {
				$del[] = $id;
			}
		}

		return (bool)$this->pdo->queryExec(
			sprintf(
				"DELETE FROM usercart WHERE id IN (%s) AND user_id = %d", implode(',', $del), $userID
			)
		);
	}

	/**
	 * Delete a release from the user's cart by release GUID.
	 *
	 * @param string $GUID   GUID of the release.
	 * @param int    $userID ID of the user.
	 */
	public function delCartByUserAndRelease($GUID, $userID)
	{
		$rel = $this->pdo->queryOneRow(sprintf("SELECT id FROM releases WHERE guid = %s", $this->pdo->escapeString($GUID)));
		if ($rel) {
			$this->pdo->queryExec(
				sprintf(
					"DELETE FROM usercart WHERE user_id = %d AND releaseid = %d",
					$userID,
					$rel["id"]
				)
			);
		}
	}

	/**
	 * Delete all items from the cart for a user.
	 *
	 * @param int $userID
	 */
	public function delCartForUser($userID)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM usercart WHERE user_id = %d", $userID));
	}

	/**
	 * Delete a release from all user's carts.
	 *
	 * @param int $releaseID ID of the release.
	 */
	public function delCartForRelease($releaseID)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM usercart WHERE releaseid = %d", $releaseID));
	}

	/**
	 * Add category exclusions for a user.
	 *
	 * @param int   $userID      ID of the user.
	 * @param array $categoryIDs List of category IDs.
	 */
	public function addCategoryExclusions($userID, $categoryIDs)
	{
		$this->delUserCategoryExclusions($userID);
		if (count($categoryIDs) > 0) {
			foreach ($categoryIDs as $categoryID) {
				$this->pdo->queryInsert(
					sprintf(
						"INSERT INTO userexcat (user_id, categoryid, createddate) VALUES (%d, %d, NOW())",
						$userID,
						$categoryID
					)
				);
			}
		}
	}

	/**
	 * Get the list of categories the user has excluded.
	 *
	 * @param int $userID ID of the user.
	 *
	 * @return array
	 */
	public function getCategoryExclusion($userID)
	{;
		$ret = array();
		$categories = $this->pdo->query(sprintf("SELECT categoryid FROM userexcat WHERE user_id = %d", $userID));
		foreach ($categories as $category) {
			$ret[] = $category["categoryid"];
		}

		return $ret;
	}

	/**
	 * Get list of category names excluded by the user.
	 *
	 * @param int $userID ID of the user.
	 *
	 * @return array
	 */
	public function getCategoryExclusionNames($userID)
	{
		$data = $this->getCategoryExclusion($userID);
		$category = new \Category(['Settings' => $this->pdo]);
		$categories = $category->getByIds($data);
		$ret = array();
		if ($categories !== false) {
			foreach ($categories as $cat) {
				$ret[] = $cat["title"];
			}
		}
		return $ret;
	}

	/**
	 * Remove a excluded category for a user.
	 *
	 * @param int $userID     ID of the user.
	 * @param int $categoryID ID of the category.
	 */
	public function delCategoryExclusion($userID, $categoryID)
	{
		$this->pdo->queryExec(sprintf("DELETE userexcat WHERE user_id = %d AND categoryid = %d", $userID, $categoryID));
	}

	/**
	 * Remove all excluded categories for a user.
	 *
	 * @param int $userID ID of the user.
	 */
	public function delUserCategoryExclusions($userID)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM userexcat WHERE user_id = %d", $userID));
	}

	/**
	 * Send a email for a invitation request.
	 *
	 * @param string $siteTitle Name of the admin's website.
	 * @param string $siteEmail Email of the admin's website.
	 * @param string $serverURL Address of the admin's website.
	 * @param int    $userID    ID of the user sending the request.
	 * @param string $emailTo   Email of the person to send the request.
	 *
	 * @return string
	 */
	public function sendInvite($siteTitle, $siteEmail, $serverURL, $userID, $emailTo)
	{
		$sender = $this->getById($userID);
		$token = $this->hashSHA1(uniqid());
		$subject = $siteTitle . " Invitation";
		$url = $serverURL . "register?invitecode=" . $token;
		if (!is_null($sender['firstname']) || $sender['firstname'] != '') {
			$contents =
				$sender["firstname"] . " " . $sender["lastname"] .
				" has sent an invite to join " . $siteTitle .
				" to this email address.<br>To accept the invitation click <a href=\"$url\">this link</a>\n";
		} else {
			$contents =
				$sender["username"] .
				" has sent an invite to join " . $siteTitle .
				" to this email address.<br>To accept the invitation click <a href=\"$url\">this link</a>\n";
		}

		nzedb\utility\sendEmail($emailTo, $subject, $contents, $siteEmail);
		$this->addInvite($userID, $token);

		return $url;
	}

	/**
	 * Get details on a invitation.
	 *
	 * @param string $inviteToken
	 *
	 * @return array|bool
	 */
	public function getInvite($inviteToken)
	{
		// Tidy any old invites sent greater than DEFAULT_INVITE_EXPIRY_DAYS days ago.
		$this->pdo->queryExec(
			sprintf(
				"DELETE FROM userinvite WHERE createddate < NOW() - INTERVAL %d DAY",
				\Users::DEFAULT_INVITE_EXPIRY_DAYS
			)
		);


		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT * FROM userinvite WHERE guid = %s",
				$this->pdo->escapeString($inviteToken)
			)
		);
	}

	/**
	 * Add a invitation request to the DB.
	 *
	 * @param int    $userID      ID of the user sending the request.
	 * @param string $inviteToken Token used to verify the request.
	 */
	public function addInvite($userID, $inviteToken)
	{
		$this->pdo->queryInsert(
			sprintf(
				"INSERT INTO userinvite (guid, user_id, createddate) VALUES (%s, %d, NOW())",
				$this->pdo->escapeString($inviteToken),
				$userID
			)
		);
	}

	/**
	 * Delete a invitation.
	 *
	 * @param string $inviteToken Token used to verify a invitation.
	 */
	public function deleteInvite($inviteToken)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM userinvite WHERE guid = %s ", $this->pdo->escapeString($inviteToken)));
	}

	/**
	 * If a invite is used, decrement the person who invited's invite count.
	 *
	 * @param int $inviteCode
	 *
	 * @return int
	 */
	public function checkAndUseInvite($inviteCode)
	{
		$invite = $this->getInvite($inviteCode);
		if (!$invite) {
			return -1;
		}

		$this->pdo->queryExec(sprintf("UPDATE users SET invites = invites-1 WHERE id = %d ", $invite["user_id"]));
		$this->deleteInvite($inviteCode);
		return $invite["user_id"];
	}

	/**
	 * Get list of top downloaders.
	 *
	 * @return array
	 */
	public function getTopGrabbers()
	{
		return $this->pdo->query("
			SELECT id, username, SUM(grabs) AS grabs
			FROM users
			GROUP BY id, username HAVING SUM(grabs) > 0
			ORDER BY grabs DESC
			LIMIT 10"
		);
	}

	/**
	 * Get list of user roles.
	 *
	 * @return array
	 */
	public function getRoles()
	{
		return $this->pdo->query("SELECT * FROM userroles");
	}

	/**
	 * Get a role by role id.
	 *
	 * @param int $roleID ID of the role.
	 *
	 * @return array|bool
	 */
	public function getRoleById($roleID)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM userroles WHERE id = %d", $roleID));
	}

	/**
	 * Get info on the default role.
	 *
	 * @return array|bool
	 */
	public function getDefaultRole()
	{
		return $this->pdo->queryOneRow("SELECT * FROM userroles WHERE isdefault = 1");
	}

	/**
	 * Add a new role.
	 *
	 * @param string $name             Name of the role.
	 * @param int    $apiRequests      Max # of api requests per day.
	 * @param int    $downloadRequests Max # of NZB downloads per day.
	 * @param int    $defaultInvites   Max invites the user can send.
	 * @param int    $canPreview       Can the user view previews or not.
	 *
	 * @return bool|int
	 */
	public function addRole($name, $apiRequests, $downloadRequests, $defaultInvites, $canPreview)
	{
		return $this->pdo->queryInsert(
			sprintf("INSERT INTO userroles (name, apirequests, downloadrequests, defaultinvites, canpreview) VALUES (%s, %d, %d, %d, %d)",
				$this->pdo->escapeString($name), $apiRequests, $downloadRequests, $defaultInvites, $canPreview
			)
		);
	}

	/**
	 * Update an existing role.
	 *
	 * @param int    $id               ID of the role.
	 * @param string $name             Name of the role.
	 * @param int    $apiRequests      Max # of api requests per day.
	 * @param int    $downloadRequests Max # of NZB downloads per day.
	 * @param int    $defaultInvites   Max # of invites the user can send.
	 * @param int    $isDefault        Is this the default role?
	 * @param int    $canPreview       Can the user view previews?
	 *
	 * @return bool|PDOStatement
	 */
	public function updateRole($id, $name, $apiRequests, $downloadRequests, $defaultInvites, $isDefault, $canPreview)
	{
		if ($isDefault == 1) {
			$this->pdo->queryExec("UPDATE userroles SET isdefault = 0");
		}

		return $this->pdo->queryExec(
			sprintf("
				UPDATE userroles
				SET name = %s, apirequests = %d, downloadrequests = %d, defaultinvites = %d, isdefault = %d, canpreview = %d
				WHERE id = %d",
				$this->pdo->escapeString($name),
				$apiRequests,
				$downloadRequests,
				$defaultInvites,
				$isDefault,
				$canPreview,
				$id
			)
		);
	}

	/**
	 * Delete a role by ID.
	 *
	 * @param int $id ID of the role.
	 *
	 * @return bool|PDOStatement
	 */
	public function deleteRole($id)
	{
		$res = $this->pdo->query(sprintf("SELECT id FROM users WHERE role = %d", $id));
		if (sizeof($res) > 0) {
			$userids = array();
			foreach ($res as $user) {
				$userids[] = $user['id'];
			}

			$defaultrole = $this->getDefaultRole();
			$this->pdo->queryExec(sprintf("UPDATE users SET role = %d WHERE id IN (%s)", $defaultrole['id'], implode(',', $userids)));
		}
		return $this->pdo->queryExec(sprintf("DELETE FROM userroles WHERE id = %d", $id));
	}

	/**
	 * Get the quantity of API requests in the last day for the user_id.
	 *
	 * @param int $userID
	 *
	 * @return array|bool
	 */
	public function getApiRequests($userID)
	{
		// Clear old requests.
		$this->clearApiRequests($userID);
		return $this->pdo->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM userrequests WHERE user_id = %d', $userID));
	}

	/**
	 * Delete api requests older than a day.
	 *
	 * @param int|bool  $userID
	 *                   int The users ID.
	 *                   bool false do all user ID's..
	 *
	 * @return void
	 */
	protected function clearApiRequests($userID)
	{
		if ($userID === false) {
			$this->pdo->queryExec('DELETE FROM userrequests WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)');
		} else {
			$this->pdo->queryExec(
				sprintf(
					'DELETE FROM userrequests WHERE user_id = %d AND timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)',
					$userID
				)
			);
		}
	}

	/**
	 * If a user accesses the API, log it.
	 *
	 * @param int    $userID  ID of the user.
	 * @param string $request The API request.
	 *
	 * @return bool|int
	 */
	public function addApiRequest($userID, $request)
	{
		return $this->pdo->queryInsert(
			sprintf(
				"INSERT INTO userrequests (user_id, request, timestamp) VALUES (%d, %s, NOW())",
				$userID,
				$this->pdo->escapeString($request)
			)
		);
	}

	/**
	 * Get the count of how many NZB's the user has downloaded in the past day.
	 *
	 * @param int $userID
	 *
	 * @return array|bool
	 */
	public function getDownloadRequests($userID)
	{
		// Clear old requests.
		$this->pdo->queryExec(
			sprintf(
				'DELETE FROM userdownloads WHERE user_id = %d AND timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)',
				$userID
			)
		);
		return $this->pdo->queryOneRow(
			sprintf(
				'SELECT COUNT(id) AS num FROM userdownloads WHERE user_id = %d AND timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)',
				$userID
			)
		);
	}

	/**
	 * If a user downloads a NZB, log it.
	 *
	 * @param int $userID ID of the user.
	 *
	 * @return bool|int
	 */
	public function addDownloadRequest($userID)
	{
		return $this->pdo->queryInsert(
			sprintf(
				"INSERT INTO userdownloads (user_id, timestamp) VALUES (%d, NOW())",
				$userID
			)
		);
	}
}
