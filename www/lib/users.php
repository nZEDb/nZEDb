<?php
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/site.php');
require_once(WWW_DIR.'lib/releases.php');
require_once(WWW_DIR.'lib/forum.php');
require_once(WWW_DIR.'lib/util.php');
require_once(WWW_DIR.'lib/releasecomments.php');
require_once(WWW_DIR.'lib/usermovies.php');
require_once(WWW_DIR.'lib/userseries.php');

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

	public function get()
	{
		$db = new DB();
		return $db->query("SELECT * FROM users");
	}

	public function delete($id)
	{
		$db = new DB();
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

	public function getRange($start, $num, $orderby, $username='', $email='', $host='', $role='')
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		$like = 'ILIKE';
		if ($db->dbSystem() == 'mysql')
			$like = 'LIKE';

		$usql = $esql = $hsql = $rsql = '';
		if ($username != '')
			$usql = sprintf(" AND users.username %s %s ", $like, $db->escapeString("%".$username."%"));

		if ($email != '')
			$esql = sprintf(" AND users.email %s %s ", $like, $db->escapeString("%".$email."%"));

		if ($host != '')
			$hsql = sprintf(" AND users.host %s %s ", $like, $db->escapeString("%".$host."%"));

		if ($role != '')
			$rsql = sprintf(" AND users.role = %d ", $role);

		$order = $this->getBrowseOrder($orderby);
		return $db->query(sprintf("SELECT users.*, userroles.name AS rolename FROM users INNER JOIN userroles ON userroles.id = users.role WHERE 1=1 %s %s %s %s ORDER BY %s %s".$limit, $usql, $esql, $hsql, $rsql, $order[0], $order[1]));
	}

	public function getBrowseOrder($orderby)
	{
		$order = ($orderby == '') ? 'username_desc' : $orderby;
		$orderArr = explode("_", $order);
		switch($orderArr[0]) {
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
		$db = new DB();
		$res = $db->queryOneRow("SELECT COUNT(id) AS num FROM users");
		return $res["num"];
	}

	public function add($uname, $pass, $email, $role, $host, $invites=Users::DEFAULT_INVITES, $invitedby=0)
	{
		$db = new DB();

		$site = new Sites();
		$s = $site->get();
		if ($s->storeuserips != "1")
			$host = "";

		if ($invitedby == 0)
			$invitedby = "null";

		return $db->queryInsert(sprintf("INSERT INTO users (username, password, email, role, createddate, host, rsstoken, invites, invitedby, userseed) VALUES (%s, %s, LOWER(%s), %d, NOW(), %s, MD5(%s), %d, %s, MD5(%s))", $db->escapeString($uname), $db->escapeString($this->hashPassword($pass)), $db->escapeString($email), $role, $db->escapeString($host), $db->escapeString(uniqid()), $invites, $invitedby, $db->escapeString($db->uuid())));
	}

	public function update($id, $uname, $email, $grabs, $role, $invites, $movieview, $musicview, $consoleview, $bookview, $saburl=false, $sabapikey=false, $sabpriority=false, $sabapikeytype=false)
	{
		$db = new DB();

		$uname = trim($uname);
		$email = trim($email);

		if (!$this->isValidUsername($uname))
			return Users::ERR_SIGNUP_BADUNAME;

		if (!$this->isValidEmail($email))
			return Users::ERR_SIGNUP_BADEMAIL;

		$res = $this->getByUsername($uname);
		if ($res)
			if ($res["id"] != $id)
				return Users::ERR_SIGNUP_UNAMEINUSE;

		$res = $this->getByEmail($email);
		if ($res)
			if ($res["id"] != $id)
				return Users::ERR_SIGNUP_EMAILINUSE;

		$sql = array();

		$sql[] = sprintf('username = %s', $db->escapeString($uname));
		$sql[] = sprintf('email = %s', $db->escapeString($email));
		$sql[] = sprintf('grabs = %d', $grabs);
		$sql[] = sprintf('role = %d', $role);
		$sql[] = sprintf('invites = %d', $invites);
		$sql[] = sprintf('movieview = %d', $movieview);
		$sql[] = sprintf('musicview = %d', $musicview);
		$sql[] = sprintf('consoleview = %d', $consoleview);
		$sql[] = sprintf('bookview = %d', $bookview);

		if ($saburl !== false)
			$sql[] = sprintf('saburl = %s', $db->escapeString($saburl));
		if ($sabapikey !== false)
			$sql[] = sprintf('sabapikey = %s', $db->escapeString($sabapikey));
		if ($sabpriority !== false)
			$sql[] = sprintf('sabpriority = %d', $sabpriority);
		if ($sabapikeytype !== false)
			$sql[] = sprintf('sabapikeytype = %d', $sabapikeytype);

		$db->queryExec(sprintf("UPDATE users SET %s WHERE id = %d", implode(', ', $sql), $id));

		return Users::SUCCESS;
	}

	public function updateRssKey($uid)
	{
		$db = new DB();
		return $db->queryExec(sprintf("UPDATE users SET rsstoken = MD5(%s) WHERE id = %d",
			$db->escapeString(uniqid()), $uid));
	}

	public function updatePassResetGuid($id, $guid)
	{
		$db = new DB();
		$db->queryExec(sprintf("UPDATE users SET resetguid = %s WHERE id = %d", $db->escapeString($guid), $id));
		return Users::SUCCESS;
	}

	public function updatePassword($id, $password)
	{
		$db = new DB();
		$db->queryExec(sprintf("UPDATE users SET password = %s, userseed = MD5(%s) WHERE id = %d", $db->escapeString($this->hashPassword($password)), $db->escapeString($db->uuid()), $id));
		return Users::SUCCESS;
	}

	public function getByEmail($email)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM users WHERE LOWER(email) = LOWER(%s) ", $db->escapeString($email)));
	}

	public function getByPassResetGuid($guid)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM users WHERE LOWER(resetguid) = LOWER(%s) ", $db->escapeString($guid)));
	}

	public function getByUsername($uname)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select * FROM users WHERE LOWER(username) = LOWER(%s) ", $db->escapeString($uname)));
	}

	public function incrementGrabs($id, $num=1)
	{
		$db = new DB();
		$db->queryExec(sprintf("UPDATE users SET grabs = grabs + %d WHERE id = %d ", $num, $id));
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT users.*, userroles.name AS rolename, userroles.canpreview, userroles.apirequests, userroles.downloadrequests, NOW() AS now FROM users INNER JOIN userroles ON userroles.id = users.role WHERE users.id = %d", $id));
	}

	public function getByIdAndRssToken($id, $rsstoken)
	{
		$db = new DB();
		$res = $this->getById($id);
		return ($res && $res["rsstoken"] == $rsstoken ? $res : null);
	}

	public function getByRssToken($rsstoken)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT users.*, userroles.apirequests, userroles.downloadrequests, NOW() AS now FROM users INNER JOIN userroles ON userroles.id = users.role WHERE LOWER(users.rsstoken) = LOWER(%s) ", $db->escapeString($rsstoken)));
	}

	public function getBrowseOrdering()
	{
		return array('username_asc', 'username_desc', 'email_asc', 'email_desc', 'host_asc', 'host_desc', 'createddate_asc', 'createddate_desc', 'lastlogin_asc', 'lastlogin_desc', 'apiaccess_asc', 'apiaccess_desc', 'grabs_asc', 'grabs_desc', 'role_asc', 'role_desc');
	}

	public function isValidUsername($uname)
	{
		return preg_match("/^[a-z][a-z0-9]{2,}$/i", $uname);
	}

	public function isValidPassword($pass)
	{
		return (strlen($pass) > 5);
	}

	public function isDisabled($username)
	{
	  $db = new DB();
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
		return "u".substr(md5(uniqid()), 0, 7);
	}

	public function generatePassword()
	{
		return substr(md5(uniqid()), 0, 8);
	}

	public function signup($uname, $pass, $email, $host, $role = Users::ROLE_USER, $invites=Users::DEFAULT_INVITES, $invitecode="", $forceinvitemode=false)
	{
		$site = new Sites();
		$s = $site->get();

		$uname = trim($uname);
		$pass = trim($pass);
		$email = trim($email);

		if (!$this->isValidUsername($uname))
			return Users::ERR_SIGNUP_BADUNAME;

		if (!$this->isValidPassword($pass))
			return Users::ERR_SIGNUP_BADPASS;

		if (!$this->isValidEmail($email))
			return Users::ERR_SIGNUP_BADEMAIL;

		$res = $this->getByUsername($uname);
		if ($res)
			return Users::ERR_SIGNUP_UNAMEINUSE;

		$res = $this->getByEmail($email);
		if ($res)
			return Users::ERR_SIGNUP_EMAILINUSE;

		// Make sure this is the last check, as if a further validation check failed, the invite would still have been used up.
		$invitedby = 0;
		if (($s->registerstatus == Sites::REGISTER_STATUS_INVITE) && !$forceinvitemode)
		{
			if ($invitecode == '')
				return Users::ERR_SIGNUP_BADINVITECODE;

			$invitedby = $this->checkAndUseInvite($invitecode);
			if ($invitedby < 0)
				return Users::ERR_SIGNUP_BADINVITECODE;
		}

		return $this->add($uname, $pass, $email, $role, $host, $invites, $invitedby);
	}

	function randomKey($amount)
	{
		$keyset  = "abcdefghijklmABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$randkey = "";
		for ($i=0; $i<$amount; $i++)
			$randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
		return $randkey;
	}

	public static function hashPassword($password)
	{
		$salt = self::randomKey(self::SALTLEN);
		$site = new Sites();
		$s = $site->get();
		return self::hashSHA1($s->siteseed.$password.$salt.$s->siteseed).$salt;
	}

	public static function hashSHA1($string)
	{
		return sha1($string);
	}

	public static function checkPassword($password, $hash)
	{
		$salt = substr($hash, -self::SALTLEN);
		$site = new Sites();
		$s = $site->get();
		return self::hashSHA1($s->siteseed.$password.$salt.$s->siteseed) === substr($hash, 0, self::SHA1LEN);
	}

	public function isLoggedIn()
	{
		if (isset($_SESSION['uid']))
			return true;
		elseif (isset($_COOKIE['uid']) && isset($_COOKIE['idh']))
		{
			$u = $this->getById($_COOKIE['uid']);

		 	if (($_COOKIE['idh'] == $this->hashSHA1($u["userseed"].$_COOKIE['uid'])) && ($u["role"] != Users::ROLE_DISABLED) )
				$this->login($_COOKIE['uid'], $_SERVER['REMOTE_ADDR']);
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
		setcookie('uid', '', (time()-2592000));
		setcookie('idh', '', (time()-2592000));
	}

	public function login($uid, $host="", $remember="")
	{
		$_SESSION['uid'] = $uid;

		$site = new Sites();
		$s = $site->get();

		if ($s->storeuserips != "1")
			$host = '';

		$this->updateSiteAccessed($uid, $host);

		if ($remember == 1)
			$this->setCookies($uid);
	}

	public function updateSiteAccessed($uid, $host="")
	{
		$db = new DB();
		$hostSql = '';
		if ($host != '')
			$hostSql = sprintf(', host = %s', $db->escapeString($host));

		$db->queryExec(sprintf("UPDATE users SET lastlogin = NOW() %s WHERE id = %d ", $hostSql, $uid));
	}

	public function updateApiAccessed($uid)
	{
		$db = new DB();
		$db->queryExec(sprintf("UPDATE users SET apiaccess = NOW() WHERE id = %d ", $uid));
	}

	public function setCookies($uid)
	{
		if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
			$secure_cookie = "1";
		else
			$secure_cookie = "0";
		$u = $this->getById($uid);
		$idh = $this->hashSHA1($u["userseed"].$uid);
		setcookie('uid', $uid, (time()+2592000), '/', '', $secure_cookie, 'true');
		setcookie('idh', $idh, (time()+2592000), '/', '', $secure_cookie, 'true');
	}

	public function addCart($uid, $releaseid)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO usercart (userid, releaseid, createddate) VALUES (%d, %d, NOW())", $uid, $releaseid));
	}

	public function getCart($uid)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT usercart.*, releases.searchname, releases.guid FROM usercart INNER JOIN releases ON releases.id = usercart.releaseid WHERE userid = %d", $uid));
	}

	public function delCart($ids, $uid)
	{
		if (!is_array($ids))
			return false;

		$del = array();
		foreach ($ids as $id)
		{
			$id = sprintf("%d", $id);
			if (!empty($id))
				$del[] = $id;
		}
		$db = new DB();
		$sql = sprintf("DELETE FROM usercart WHERE id IN (%s) AND userid = %d", implode(',',$del), $uid);
		$db->queryExec($sql);
	}

	public function delCartByUserAndRelease($guid, $uid)
	{
		$db = new DB();
		$rel = $db->queryOneRow(sprintf("SELECT id FROM releases WHERE guid = %s", $db->escapeString($guid)));
		if ($rel)
			$db->queryExec(sprintf("DELETE FROM usercart WHERE userid = %d AND releaseid = %d", $uid, $rel["id"]));
	}

	public function delCartForUser($uid)
	{
		$db = new DB();
		$db->queryExec(sprintf("DELETE FROM usercart WHERE userid = %d", $uid));
	}

	public function delCartForRelease($rid)
	{
		$db = new DB();
		$db->queryExec(sprintf("DELETE FROM usercart WHERE releaseid = %d", $rid));
	}

	public function addCategoryExclusions($uid, $catids)
	{
		$db = new DB();
		$this->delUserCategoryExclusions($uid);
		if (count($catids) > 0)
		{
			foreach ($catids as $catid)
			{
				$db->queryInsert(sprintf("INSERT INTO userexcat (userid, categoryid, createddate) VALUES (%d, %d, NOW())", $uid, $catid));
			}
		}
	}

	public function getCategoryExclusion($uid)
	{
		$db = new DB();
		$ret = array();
		$data = $db->query(sprintf("SELECT categoryid FROM userexcat WHERE userid = %d", $uid));
		foreach ($data as $d)
			$ret[] = $d["categoryid"];

		return $ret;
	}

	public function getCategoryExclusionNames($uid)
	{
		$data = $this->getCategoryExclusion($uid);
		$db = new DB();
		$category = new Category();
		$data = $category->getByIds($data);
		$ret = array();
		if ($data !== false)
		{
			foreach ($data as $d)
				$ret[] = $d["title"];
		}
		return $ret;
	}

	public function delCategoryExclusion($uid, $catid)
	{
		$db = new DB();
		$db->queryExec(sprintf("DELETE userexcat WHERE userid = %d AND categoryid = %d", $uid, $catid));
	}

	public function delUserCategoryExclusions($uid)
	{
		$db = new DB();
		$db->queryExec(sprintf("DELETE FROM userexcat WHERE userid = %d", $uid));
	}

	public function sendInvite($sitetitle, $siteemail, $serverurl, $uid, $emailto)
	{
		$sender = $this->getById($uid);
		$token = $this->hashSHA1(uniqid());
		$subject = $sitetitle." Invitation";
		$url = $serverurl."register?invitecode=".$token;
		$contents = $sender["username"]." has sent an invite to join ".$sitetitle." to this email address. To accept the invition click the following link.\n\n ".$url;

		sendEmail($emailto, $subject, $contents, $siteemail);
		$this->addInvite($uid, $token);

		return $url;
	}

	public function getInvite($inviteToken)
	{
		$db = new DB();
		// Tidy any old invites sent greater than DEFAULT_INVITE_EXPIRY_DAYS days ago.
		if ($db->dbSystem() == 'mysql')
			$db->queryExec(sprintf("DELETE FROM userinvite WHERE createddate < NOW() - INTERVAL %d DAY", Users::DEFAULT_INVITE_EXPIRY_DAYS));
		else
			$db->queryExec(sprintf("DELETE FROM userinvite WHERE createddate < NOW() - INTERVAL '%d DAYS'", Users::DEFAULT_INVITE_EXPIRY_DAYS));

		return $db->queryOneRow(sprintf("SELECT * FROM userinvite WHERE guid = %s", $db->escapeString($inviteToken)));
	}

	public function addInvite($uid, $inviteToken)
	{
		$db = new DB();
		$db->queryInsert(sprintf("INSERT INTO userinvite (guid, userid, createddate) VALUES (%s, %d, NOW())", $db->escapeString($inviteToken), $uid));
	}

	public function deleteInvite($inviteToken)
	{
		$db = new DB();
		$db->queryExec(sprintf("DELETE FROM userinvite WHERE guid = %s ", $db->escapeString($inviteToken)));
	}

	public function checkAndUseInvite($invitecode)
	{
		$invite = $this->getInvite($invitecode);
		if (!$invite)
			return -1;

		$db = new DB();
		$db->queryExec(sprintf("UPDATE users SET invites = invites-1 WHERE id = %d ", $invite["userid"]));
		$this->deleteInvite($invitecode);
		return $invite["userid"];
	}

	public function getTopGrabbers()
	{
		$db = new DB();
		return $db->query("SELECT id, username, SUM(grabs) AS grabs FROM users GROUP BY id, username HAVING SUM(grabs) > 0 ORDER BY grabs DESC LIMIT 10");
	}

	public function getRoles()
	{
		$db = new DB();
		return $db->query("SELECT * FROM userroles");
	}

	public function getRoleById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM userroles WHERE id = %d", $id));
	}

	public function getDefaultRole()
	{
		$db = new DB();
		return $db->queryOneRow("SELECT * FROM userroles WHERE isdefault = 1");
	}

	public function addRole($name, $apirequests, $downloadrequests, $defaultinvites, $canpreview)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO userroles (name, apirequests, downloadrequests, defaultinvites, canpreview) values (%s, %d, %d, %d, %d)", $db->escapeString($name), $apirequests, $downloadrequests, $defaultinvites, $canpreview));
	}

	public function updateRole($id, $name, $apirequests, $downloadrequests, $defaultinvites, $isdefault, $canpreview)
	{
		$db = new DB();
		if ($isdefault == 1)
			$db->queryExec("UPDATE userroles SET isdefault = 0");

		return $db->queryExec(sprintf("UPDATE userroles SET name = %s, apirequests = %d, downloadrequests = %d, defaultinvites = %d, isdefault = %d, canpreview = %d WHERE id = %d", $db->escapeString($name), $apirequests, $downloadrequests, $defaultinvites, $isdefault, $canpreview, $id));
	}

	public function deleteRole($id)
	{
		$db = new DB();
		$res = $db->query(sprintf("SELECT id FROM users WHERE role = %d", $id));
		if (sizeof($res) > 0)
		{
			$userids = array();
			foreach($res as $user)
				$userids[] = $user['id'];

			$defaultrole = $this->getDefaultRole();
			$db->queryExec(sprintf("UPDATE users SET role = %d WHERE id IN (%s)", $defaultrole['id'], implode(',', $userids)));
		}
		return $db->queryExec(sprintf("DELETE FROM userroles WHERE id = %d", $id));
	}

	public function getApiRequests($userid)
	{
		$db = new DB();
		// Clear old requests.
		if ($db->dbSystem() == 'mysql')
		{
			$db->queryExec(sprintf('DELETE FROM userrequests WHERE userid = %d AND timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
			return $db->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM userrequests WHERE userid = %d AND timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
		}
		else
		{
			$db->queryExec(sprintf("DELETE FROM userrequests WHERE userid = %d AND timestamp < (NOW() - INTERVAL '1 DAY')", $userid));
			return $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM userrequests WHERE userid = %d AND timestamp > (NOW() - INTERVAL '1 DAY')", $userid));
		}
	}

	public function addApiRequest($userid, $request)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO userrequests (userid, request, timestamp) VALUES (%d, %s, NOW())", $userid, $db->escapeString($request)));
	}

	public function getDownloadRequests($userid)
	{
		$db = new DB();
		// Clear old requests.
		if ($db->dbSystem() == 'mysql')
		{
			$db->queryExec(sprintf('DELETE FROM userdownloads WHERE userid = %d AND timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
			return $db->queryOneRow(sprintf('select COUNT(id) AS num FROM userdownloads WHERE userid = %d AND timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)', $userid));
		}
		else
		{
			$db->queryExec(sprintf("DELETE FROM userdownloads WHERE userid = %d AND timestamp < (NOW() - INTERVAL '1 DAY')", $userid));
			return $db->queryOneRow(sprintf("select COUNT(id) AS num FROM userdownloads WHERE userid = %d AND timestamp > (NOW() - INTERVAL '1 DAY')", $userid));
		}
	}

	public function addDownloadRequest($userid)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO userdownloads (userid, timestamp) VALUES (%d, NOW())", $userid));
	}
}
