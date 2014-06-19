<?php

use nzedb\db\DB;

use nzedb\utility;

class Users
{
	const ERR_SIGNUP_BADUNAME = -1;
	const ERR_SIGNUP_BADPASS = -2;
	const ERR_SIGNUP_BADEMAIL = -3;
	const ERR_SIGNUP_UNAMEINUSE = -4;
	const ERR_SIGNUP_EMAILINUSE = -5;
	const ERR_SIGNUP_BADINVITECODE = -6;
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

	function __construct()
	{
		$this->db = new DB();
	}

	public function get()
	{
		$db = $this->db;
		return $db->query("SELECT * FROM users");
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
		return $this->db->queryOneRow("SELECT style FROM users WHERE id = " . $userID);
	}

	public function delete($id)
	{
		$db = $this->db;
		$this->delCartForUser($id);
		$this->delUserCategoryExclusions($id);

		$rc = new ReleaseComments();
		$rc->deleteCommentsForUser($id);

		$um = new UserMovies();
		$um->delMovieForUser($id);

		$us = new UserSeries();
		$us->delShowForUser($id);

		$forum = new Forum();
		$forum->deleteUser($id);

		$db->queryExec(sprintf("DELETE FROM users WHERE id = %d", $id));
	}

	public function getRange($start, $num, $orderby, $username = '', $email = '', $host = '', $role = '', $apiRequests = false)
	{
		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		$like = 'ILIKE';
		if ($this->db->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}

		$usql = $esql = $hsql = $rsql = '';
		if ($username != '') {
			$usql = sprintf(" AND users.username %s %s ", $like, $this->db->escapeString("%" . $username . "%"));
		}

		if ($email != '') {
			$esql = sprintf(" AND users.email %s %s ", $like, $this->db->escapeString("%" . $email . "%"));
		}

		if ($host != '') {
			$hsql = sprintf(" AND users.host %s %s ", $like, $this->db->escapeString("%" . $host . "%"));
		}

		if ($role != '') {
			$rsql = sprintf(" AND users.role = %d ", $role);
		}

		$ret = array();
		$order = $this->getBrowseOrder($orderby);
		if ($apiRequests) {
			$this->clearApiRequests(false);

			$ret = $this->db->query(sprintf("
				SELECT users.*,
				userroles.name AS rolename,
				COUNT(userrequests.id) AS apirequests
				FROM users
				INNER JOIN userroles ON userroles.id = users.role
				LEFT JOIN userrequests ON userrequests.userid = users.id
				WHERE users.id != 0 %s %s %s %s
				AND email != 'sharing@nZEDb.com'
				GROUP BY users.id
				ORDER BY %s %s" .
				$limit,
				$usql, $esql, $hsql, $rsql,
				$order[0], $order[1]));
		} else {
			$ret = $this->db->query(sprintf("SELECT users.*, userroles.name AS rolename FROM users INNER JOIN userroles ON userroles.id = users.role WHERE 1=1 %s %s %s %s ORDER BY %s %s" . $limit, $usql, $esql, $hsql, $rsql, $order[0], $order[1]));
		}

		return $ret;
	}

	public function getBrowseOrder($orderby)
	{
		$order = ($orderby == '') ? 'username_desc' : $orderby;
		$orderArr = explode("_", $order);
		switch ($orderArr[0]) {
			case 'username':
				$orderfield = 'username';
				break;
			case 'email':
				$orderfield = 'email';
				break;
			case 'host':
				$orderfield = 'host';
				break;
			case 'createddate':
				$orderfield = 'createddate';
				break;
			case 'lastlogin':
				$orderfield = 'lastlogin';
				break;
			case 'apiaccess':
				$orderfield = 'apiaccess';
				break;
			case 'grabs':
				$orderfield = 'grabs';
				break;
			case 'role':
				$orderfield = 'role';
				break;
			default:
				$orderfield = 'username';
				break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderfield, $ordersort);
	}

	public function getCount()
	{
		$db = $this->db;
		$res = $db->queryOneRow("SELECT COUNT(id) AS num FROM users WHERE email != 'sharing@nZEDb.com'");
		return $res["num"];
	}

	public function add($uname, $fname, $lname, $pass, $email, $role, $host, $invites = Users::DEFAULT_INVITES, $invitedby = 0)
	{
		$db = $this->db;

		$site = new Sites();
		$s = $site->get();
		if ($s->storeuserips != "1") {
			$host = "";
		}

		if ($invitedby == 0) {
			$invitedby = "null";
		}

		return $db->queryInsert(sprintf("INSERT INTO users (username, password, email, role, createddate, host, rsstoken, invites, invitedby, userseed, firstname, lastname) VALUES (%s, %s, LOWER(%s), %d, NOW(), %s, MD5(%s), %d, %s, MD5(%s), %s, %s)", $db->escapeString($uname), $db->escapeString($this->hashPassword($pass)), $db->escapeString($email), $role, $db->escapeString($host), $db->escapeString(uniqid()), $invites, $invitedby, $db->escapeString($db->uuid()), $db->escapeString($fname), $db->escapeString($lname)));
	}

	public function update($id, $uname, $fname, $lname, $email, $grabs, $role, $invites,
		$movieview, $musicview, $consoleview, $gameview, $bookview,
		$cp_url = false, $cp_api = false, $style = 'None', $queueType = '',
		$nzbgetURL = '', $nzbgetUsername = '', $nzbgetPassword = '',
		$saburl = '', $sabapikey = '', $sabpriority = '', $sabapikeytype = '')
	{
		$db = $this->db;

		$uname = trim($uname);
		$fname = trim($fname);
		$lname = trim($lname);
		$email = trim($email);

		if (!$this->isValidUsername($uname)) {
			return Users::ERR_SIGNUP_BADUNAME;
		}

		if (!$this->isValidEmail($email)) {
			return Users::ERR_SIGNUP_BADEMAIL;
		}

		$res = $this->getByUsername($uname);
		if ($res) {
			if ($res["id"] != $id) {
				return Users::ERR_SIGNUP_UNAMEINUSE;
			}
		}

		$res1 = $this->getByEmail($email);
		if ($res1) {
			if ($res1["id"] != $id) {
				return Users::ERR_SIGNUP_EMAILINUSE;
			}
		}

		$sql = array();

		$sql[] = sprintf('username = %s', $db->escapeString($uname));
		if ($fname !== false) {
			$sql[] = sprintf('firstname = %s', $db->escapeString($fname));
		}
		if ($lname !== false) {
			$sql[] = sprintf('lastname = %s', $db->escapeString($lname));
		}
		$sql[] = sprintf('email = %s', $db->escapeString($email));
		$sql[] = sprintf('grabs = %d', $grabs);
		$sql[] = sprintf('role = %d', $role);
		$sql[] = sprintf('invites = %d', $invites);
		$sql[] = sprintf('movieview = %d', $movieview);
		$sql[] = sprintf('musicview = %d', $musicview);
		$sql[] = sprintf('consoleview = %d', $consoleview);
		$sql[] = sprintf('gameview = %d', $gameview);
		$sql[] = sprintf('bookview = %d', $bookview);
		$sql[] = sprintf('style = %s', $db->escapeString($style));
		if ($queueType !== '') {
			$sql[] = sprintf('queuetype = %d', $queueType);
		}

		if ($nzbgetURL !== '') {
			$sql[] = sprintf('nzbgeturl = %s', $db->escapeString($nzbgetURL));
		}

		$sql[] = sprintf('nzbgetusername = %s', $db->escapeString($nzbgetUsername));
		$sql[] = sprintf('nzbgetpassword = %s', $db->escapeString($nzbgetPassword));

		if ($saburl !== '') {
			$sql[] = sprintf('saburl = %s', $db->escapeString($saburl));
		}
		if ($sabapikey !== '') {
			$sql[] = sprintf('sabapikey = %s', $db->escapeString($sabapikey));
		}
		if ($sabpriority !== '') {
			$sql[] = sprintf('sabpriority = %d', $sabpriority);
		}
		if ($sabapikeytype !== '') {
			$sql[] = sprintf('sabapikeytype = %d', $sabapikeytype);
		}

		if ($cp_url !== false) {
			$sql[] = sprintf('cp_url = %s', $db->escapeString($cp_url));
		}
		if ($cp_api !== false) {
			$sql[] = sprintf('cp_api = %s', $db->escapeString($cp_api));
		}
		$db->queryExec(sprintf("UPDATE users SET %s WHERE id = %d", implode(', ', $sql), $id));

		return Users::SUCCESS;
	}

	public function updateRssKey($uid)
	{
		$db = $this->db;
		return $db->queryExec(sprintf("UPDATE users SET rsstoken = MD5(%s) WHERE id = %d", $db->escapeString(uniqid()), $uid));
	}

	public function updatePassResetGuid($id, $guid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("UPDATE users SET resetguid = %s WHERE id = %d", $db->escapeString($guid), $id));
		return Users::SUCCESS;
	}

	public function updatePassword($id, $password)
	{
		$db = $this->db;
		$db->queryExec(sprintf("UPDATE users SET password = %s, userseed = MD5(%s) WHERE id = %d", $db->escapeString($this->hashPassword($password)), $db->escapeString($db->uuid()), $id));
		return Users::SUCCESS;
	}

	public function getByEmail($email)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("SELECT * FROM users WHERE LOWER(email) = LOWER(%s) ", $db->escapeString($email)));
	}

	public function getByPassResetGuid($guid)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("SELECT * FROM users WHERE LOWER(resetguid) = LOWER(%s) ", $db->escapeString($guid)));
	}

	public function getByUsername($uname)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("select * FROM users WHERE LOWER(username) = LOWER(%s) ", $db->escapeString($uname)));
	}

	public function incrementGrabs($id, $num = 1)
	{
		$db = $this->db;
		$db->queryExec(sprintf("UPDATE users SET grabs = grabs + %d WHERE id = %d ", $num, $id));
	}

	public function getById($id)
	{
		return $this->db->queryOneRow(sprintf("SELECT users.*, userroles.name AS rolename, userroles.canpreview, userroles.apirequests, userroles.downloadrequests, NOW() AS now FROM users INNER JOIN userroles ON userroles.id = users.role WHERE users.id = %d", $id));
	}

	public function getByIdAndRssToken($id, $rsstoken)
	{
		$res = $this->getById($id);
		return ($res && $res["rsstoken"] == $rsstoken ? $res : null);
	}

	public function getByRssToken($rsstoken)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("SELECT users.*, userroles.apirequests, userroles.downloadrequests, NOW() AS now FROM users INNER JOIN userroles ON userroles.id = users.role WHERE LOWER(users.rsstoken) = LOWER(%s) ", $db->escapeString($rsstoken)));
	}

	public function getBrowseOrdering()
	{
		return array('username_asc', 'username_desc', 'email_asc', 'email_desc', 'host_asc', 'host_desc', 'createddate_asc', 'createddate_desc', 'lastlogin_asc', 'lastlogin_desc', 'apiaccess_asc', 'apiaccess_desc', 'grabs_asc', 'grabs_desc', 'role_asc', 'role_desc');
	}

	public function isValidUsername($uname)
	{
		// Username must be at least three characters and is alphanumeric
		return ((ctype_alnum($uname) && strlen($uname) > 2) ? true : false);
	}

	public function isValidPassword($pass)
	{
		// Password must be at least 6 characters
		return (strlen($pass) > 5);
	}

	public function isDisabled($username)
	{
		$db = $this->db;
		$role = $db->queryOneRow(sprintf("SELECT role AS role FROM users WHERE username = %s", $db->escapeString($username)));
		return ($role["role"] == Users::ROLE_DISABLED);
	}

	public function isValidEmail($email)
	{
		return preg_match("/^([\w\+-]+)(\.[\w\+-]+)*@([a-z0-9-]+\.)+[a-z]{2,6}$/i", $email);
	}

	public function isValidUrl($url)
	{
		return (!preg_match('/^(http|https|ftp):\/\/([A-Z0-9][\w-]*(?:\.[A-Z0-9][\w-]*)+):?(\d+)?\/?/i', $url)) ? false : true;
	}

	public function generateUsername($email)
	{
		// TODO: Make this generate a more friendly username based on the email address.
		return "u" . substr(md5(uniqid()), 0, 7);
	}

	public function generatePassword()
	{
		return substr(md5(uniqid()), 0, 8);
	}

	public function signup($uname, $fname, $lname, $pass, $email, $host, $role = Users::ROLE_USER, $invites = Users::DEFAULT_INVITES, $invitecode = "", $forceinvitemode = false)
	{
		$site = new Sites();
		$s = $site->get();

		$uname = trim($uname);
		$fname = trim($fname);
		$lname = trim($lname);
		$pass = trim($pass);
		$email = trim($email);

		if (!$this->isValidUsername($uname)) {
			return Users::ERR_SIGNUP_BADUNAME;
		}

		if (!$this->isValidPassword($pass)) {
			return Users::ERR_SIGNUP_BADPASS;
		}

		if (!$this->isValidEmail($email)) {
			return Users::ERR_SIGNUP_BADEMAIL;
		}

		$res = $this->getByUsername($uname);
		if ($res) {
			return Users::ERR_SIGNUP_UNAMEINUSE;
		}

		$res1 = $this->getByEmail($email);
		if ($res1) {
			return Users::ERR_SIGNUP_EMAILINUSE;
		}

		// Make sure this is the last check, as if a further validation check failed, the invite would still have been used up.
		$invitedby = 0;
		if (($s->registerstatus == Sites::REGISTER_STATUS_INVITE) && !$forceinvitemode) {
			if ($invitecode == '') {
				return Users::ERR_SIGNUP_BADINVITECODE;
			}

			$invitedby = $this->checkAndUseInvite($invitecode);
			if ($invitedby < 0) {
				return Users::ERR_SIGNUP_BADINVITECODE;
			}
		}

		return $this->add($uname, $fname, $lname, $pass, $email, $role, $host, $invites, $invitedby);
	}

	function randomKey($amount)
	{
		$keyset = "abcdefghijklmABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$randkey = "";
		for ($i = 0; $i < $amount; $i++) {
			$randkey .= substr($keyset, rand(0, strlen($keyset) - 1), 1);
		}
		return $randkey;
	}

	public static function hashPassword($password)
	{
		return crypt($password);  // let the salt be automatically generated
	}

	public static function hashSHA1($string)
	{
		return sha1($string);
	}

	public static function checkPassword($password, $hash)
	{
		return (crypt($password, $hash) == $hash);
	}

	public function isLoggedIn()
	{
		if (isset($_SESSION['uid'])) {
			return true;
		} else if (isset($_COOKIE['uid']) && isset($_COOKIE['idh'])) {
			$u = $this->getById($_COOKIE['uid']);

			if (($_COOKIE['idh'] == $this->hashSHA1($u["userseed"] . $_COOKIE['uid'])) && ($u["role"] != Users::ROLE_DISABLED)) {
				$this->login($_COOKIE['uid'], $_SERVER['REMOTE_ADDR']);
			}
		}
		return isset($_SESSION['uid']);
	}

	public function currentUserId()
	{
		return (isset($_SESSION['uid']) ? $_SESSION['uid'] : -1);
	}

	public function logout()
	{
		session_unset();
		session_destroy();
		setcookie('uid', '', (time() - 2592000));
		setcookie('idh', '', (time() - 2592000));
	}

	public function login($uid, $host = "", $remember = "")
	{
		$_SESSION['uid'] = $uid;

		$site = new Sites();
		$s = $site->get();

		if ($s->storeuserips != "1") {
			$host = '';
		}

		$this->updateSiteAccessed($uid, $host);

		if ($remember == 1) {
			$this->setCookies($uid);
		}
	}

	public function updateSiteAccessed($uid, $host = "")
	{
		$db = $this->db;
		$hostSql = '';
		if ($host != '') {
			$hostSql = sprintf(', host = %s', $db->escapeString($host));
		}

		$db->queryExec(sprintf("UPDATE users SET lastlogin = NOW() %s WHERE id = %d ", $hostSql, $uid));
	}

	public function updateApiAccessed($uid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("UPDATE users SET apiaccess = NOW() WHERE id = %d ", $uid));
	}

	public function setCookies($uid)
	{
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$secure_cookie = "1";
		} else {
			$secure_cookie = "0";
		}
		$u = $this->getById($uid);
		$idh = $this->hashSHA1($u["userseed"] . $uid);
		setcookie('uid', $uid, (time() + 2592000), '/', '', $secure_cookie, 'true');
		setcookie('idh', $idh, (time() + 2592000), '/', '', $secure_cookie, 'true');
	}

	public function addCart($uid, $releaseid)
	{
		$db = $this->db;
		return $db->queryInsert(sprintf("INSERT INTO usercart (userid, releaseid, createddate) VALUES (%d, %d, NOW())", $uid, $releaseid));
	}

	public function getCart($uid)
	{
		$db = $this->db;
		return $db->query(sprintf("SELECT usercart.*, releases.searchname, releases.guid FROM usercart INNER JOIN releases ON releases.id = usercart.releaseid WHERE userid = %d", $uid));
	}

	public function delCart($ids, $uid)
	{
		if (!is_array($ids)) {
			return false;
		}

		$del = array();
		foreach ($ids as $id) {
			$id = sprintf("%d", $id);
			if (!empty($id)) {
				$del[] = $id;
			}
		}
		$db = $this->db;
		$sql = sprintf("DELETE FROM usercart WHERE id IN (%s) AND userid = %d", implode(',', $del), $uid);
		$db->queryExec($sql);
	}

	public function delCartByUserAndRelease($guid, $uid)
	{
		$db = $this->db;
		$rel = $db->queryOneRow(sprintf("SELECT id FROM releases WHERE guid = %s", $db->escapeString($guid)));
		if ($rel) {
			$db->queryExec(sprintf("DELETE FROM usercart WHERE userid = %d AND releaseid = %d", $uid, $rel["id"]));
		}
	}

	public function delCartForUser($uid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE FROM usercart WHERE userid = %d", $uid));
	}

	public function delCartForRelease($rid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE FROM usercart WHERE releaseid = %d", $rid));
	}

	public function addCategoryExclusions($uid, $catids)
	{
		$db = $this->db;
		$this->delUserCategoryExclusions($uid);
		if (count($catids) > 0) {
			foreach ($catids as $catid) {
				$db->queryInsert(sprintf("INSERT INTO userexcat (userid, categoryid, createddate) VALUES (%d, %d, NOW())", $uid, $catid));
			}
		}
	}

	public function getCategoryExclusion($uid)
	{
		$db = $this->db;
		$ret = array();
		$data = $db->query(sprintf("SELECT categoryid FROM userexcat WHERE userid = %d", $uid));
		foreach ($data as $d) {
			$ret[] = $d["categoryid"];
		}

		return $ret;
	}

	public function getCategoryExclusionNames($uid)
	{
		$data = $this->getCategoryExclusion($uid);
		$category = new Category();
		$data1 = $category->getByIds($data);
		$ret = array();
		if ($data1 !== false) {
			foreach ($data1 as $d) {
				$ret[] = $d["title"];
			}
		}
		return $ret;
	}

	public function delCategoryExclusion($uid, $catid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE userexcat WHERE userid = %d AND categoryid = %d", $uid, $catid));
	}

	public function delUserCategoryExclusions($uid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE FROM userexcat WHERE userid = %d", $uid));
	}

	public function sendInvite($sitetitle, $siteemail, $serverurl, $uid, $emailto)
	{
		$sender = $this->getById($uid);
		$token = $this->hashSHA1(uniqid());
		$subject = $sitetitle . " Invitation";
		$url = $serverurl . "register?invitecode=" . $token;
		if (!is_null($sender['firstname']) || $sender['firstname'] != '') {
			$contents = $sender["firstname"] . " " . $sender["lastname"] . " has sent an invite to join " . $sitetitle . " to this email address.<br>To accept the invitation click <a href=\"$url\">this link</a>\n";
		} else {
			$contents = $sender["username"] . " has sent an invite to join " . $sitetitle . " to this email address.<br>To accept the invitation click <a href=\"$url\">this link</a>\n";
		}

		nzedb\utility\sendEmail($emailto, $subject, $contents, $siteemail);
		$this->addInvite($uid, $token);

		return $url;
	}

	public function getInvite($inviteToken)
	{
		$db = $this->db;
		// Tidy any old invites sent greater than DEFAULT_INVITE_EXPIRY_DAYS days ago.
		if ($db->dbSystem() === 'mysql') {
			$db->queryExec(sprintf("DELETE FROM userinvite WHERE createddate < NOW() - INTERVAL %d DAY", Users::DEFAULT_INVITE_EXPIRY_DAYS));
		} else {
			$db->queryExec(sprintf("DELETE FROM userinvite WHERE createddate < NOW() - INTERVAL '%d DAYS'", Users::DEFAULT_INVITE_EXPIRY_DAYS));
		}

		return $db->queryOneRow(sprintf("SELECT * FROM userinvite WHERE guid = %s", $db->escapeString($inviteToken)));
	}

	public function addInvite($uid, $inviteToken)
	{
		$db = $this->db;
		$db->queryInsert(sprintf("INSERT INTO userinvite (guid, userid, createddate) VALUES (%s, %d, NOW())", $db->escapeString($inviteToken), $uid));
	}

	public function deleteInvite($inviteToken)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE FROM userinvite WHERE guid = %s ", $db->escapeString($inviteToken)));
	}

	public function checkAndUseInvite($invitecode)
	{
		$invite = $this->getInvite($invitecode);
		if (!$invite) {
			return -1;
		}

		$db = $this->db;
		$db->queryExec(sprintf("UPDATE users SET invites = invites-1 WHERE id = %d ", $invite["userid"]));
		$this->deleteInvite($invitecode);
		return $invite["userid"];
	}

	public function getTopGrabbers()
	{
		$db = $this->db;
		return $db->query("SELECT id, username, SUM(grabs) AS grabs FROM users GROUP BY id, username HAVING SUM(grabs) > 0 ORDER BY grabs DESC LIMIT 10");
	}

	public function getRoles()
	{
		$db = $this->db;
		return $db->query("SELECT * FROM userroles");
	}

	public function getRoleById($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("SELECT * FROM userroles WHERE id = %d", $id));
	}

	public function getDefaultRole()
	{
		$db = $this->db;
		return $db->queryOneRow("SELECT * FROM userroles WHERE isdefault = 1");
	}

	public function addRole($name, $apirequests, $downloadrequests, $defaultinvites, $canpreview)
	{
		$db = $this->db;
		return $db->queryInsert(sprintf("INSERT INTO userroles (name, apirequests, downloadrequests, defaultinvites, canpreview) values (%s, %d, %d, %d, %d)", $db->escapeString($name), $apirequests, $downloadrequests, $defaultinvites, $canpreview));
	}

	public function updateRole($id, $name, $apirequests, $downloadrequests, $defaultinvites, $isdefault, $canpreview)
	{
		$db = $this->db;
		if ($isdefault == 1) {
			$db->queryExec("UPDATE userroles SET isdefault = 0");
		}

		return $db->queryExec(sprintf("UPDATE userroles SET name = %s, apirequests = %d, downloadrequests = %d, defaultinvites = %d, isdefault = %d, canpreview = %d WHERE id = %d", $db->escapeString($name), $apirequests, $downloadrequests, $defaultinvites, $isdefault, $canpreview, $id));
	}

	public function deleteRole($id)
	{
		$db = $this->db;
		$res = $db->query(sprintf("SELECT id FROM users WHERE role = %d", $id));
		if (sizeof($res) > 0) {
			$userids = array();
			foreach ($res as $user) {
				$userids[] = $user['id'];
			}

			$defaultrole = $this->getDefaultRole();
			$db->queryExec(sprintf("UPDATE users SET role = %d WHERE id IN (%s)", $defaultrole['id'], implode(',', $userids)));
		}
		return $db->queryExec(sprintf("DELETE FROM userroles WHERE id = %d", $id));
	}

	/**
	 * Get the quantity of API requests in the last day for the userid.
	 *
	 * @param int $userid
	 *
	 * @return array|bool
	 */
	public function getApiRequests($userid)
	{
		// Clear old requests.
		$this->clearApiRequests($userid);
		return $this->db->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM userrequests WHERE userid = %d', $userid));
	}

	/**
	 * Delete api requests older than a day.
	 *
	 * @param int|bool  $userid
	 *                   int The users ID.
	 *                   bool false do all user ID's..
	 *
	 * @return void
	 */
	protected function clearApiRequests($userid)
	{
		if ($this->db->dbSystem() === 'mysql') {
			if ($userid === false) {
				$this->db->queryExec('DELETE FROM userrequests WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)');
			} else {
				$this->db->queryExec(sprintf('DELETE FROM userrequests WHERE userid = %d AND timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
			}
		} else {
			if ($userid === false) {
				$this->db->queryExec("DELETE FROM userrequests WHERE timestamp < (NOW() - INTERVAL '1 DAY')");
			} else {
				$this->db->queryExec(sprintf("DELETE FROM userrequests WHERE userid = %d AND timestamp < (NOW() - INTERVAL '1 DAY')", $userid));
			}
		}
	}

	public function addApiRequest($userid, $request)
	{
		$db = $this->db;
		return $db->queryInsert(sprintf("INSERT INTO userrequests (userid, request, timestamp) VALUES (%d, %s, NOW())", $userid, $db->escapeString($request)));
	}

	public function getDownloadRequests($userid)
	{
		$db = $this->db;
		// Clear old requests.
		if ($db->dbSystem() === 'mysql') {
			$db->queryExec(sprintf('DELETE FROM userdownloads WHERE userid = %d AND timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
			return $db->queryOneRow(sprintf('select COUNT(id) AS num FROM userdownloads WHERE userid = %d AND timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
		} else {
			$db->queryExec(sprintf("DELETE FROM userdownloads WHERE userid = %d AND timestamp < (NOW() - INTERVAL '1 DAY')", $userid));
			return $db->queryOneRow(sprintf("select COUNT(id) AS num FROM userdownloads WHERE userid = %d AND timestamp > (NOW() - INTERVAL '1 DAY')", $userid));
		}
	}

	public function addDownloadRequest($userid)
	{
		$db = $this->db;
		return $db->queryInsert(sprintf("INSERT INTO userdownloads (userid, timestamp) VALUES (%d, NOW())", $userid));
	}
}
