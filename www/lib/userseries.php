<?php
require_once(WWW_DIR."/lib/framework/db.php");

class UserSeries
{

	public function addShow($uid, $rageid, $catid=array())
	{
		$db = new DB();
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "NULL";
		return $db->queryInsert(sprintf("INSERT INTO userseries (userid, rageid, categoryid, createddate) VALUES (%d, %d, %s, NOW())", $uid, $rageid, $catid));
	}

	public function getShows($uid)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT userseries.*, tvrage.releasetitle FROM userseries INNER JOIN tvrage ON tvrage.rageid = userseries.rageid WHERE userid = %d ORDER BY tvrage.releasetitle ASC", $uid));
	}

	public function delShow($uid, $rageid)
	{
		$db = new DB();
		$db->queryDelete(sprintf("DELETE FROM userseries WHERE userid = %d AND rageid = %d", $uid, $rageid));
	}

	public function getShow($uid, $rageid)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT userseries.*, tvr.releasetitle FROM userseries LEFT OUTER JOIN tvrage tvr ON tvr.rageid = userseries.rageid WHERE userseries.userid = %d AND userseries.rageid = %d", $uid, $rageid));
	}

	public function delShowForUser($uid)
	{
		$db = new DB();
		$db->queryDelete(sprintf("DELETE FROM userseries WHERE userid = %d", $uid));
	}

	public function delShowForSeries($sid)
	{
		$db = new DB();
		$db->queryDelete(sprintf("DELETE FROM userseries WHERE rageid = %d", $rid));
	}

	public function updateShow($uid, $rageid, $catid=array())
	{
		$db = new DB();
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "NULL";
		$db->queryExec(sprintf("UPDATE userseries SET categoryid = %s WHERE userid = %d AND rageid = %d", $catid, $uid, $rageid));
	}
}
