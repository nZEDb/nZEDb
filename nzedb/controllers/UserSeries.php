<?php

use nzedb\db\Settings;

class UserSeries
{
	function __construct()
	{
		$this->pdo = new Settings();
	}

	public function addShow($uid, $rageid, $catid=array())
	{
		$pdo = $this->pdo;
		$catid = (!empty($catid)) ? $pdo->escapeString(implode('|', $catid)) : "NULL";
		return $pdo->queryInsert(sprintf("INSERT INTO userseries (userid, rageid, categoryid, createddate) VALUES (%d, %d, %s, NOW())", $uid, $rageid, $catid));
	}

	public function getShows($uid)
	{
		$pdo = $this->pdo;
		return $pdo->query(sprintf("SELECT userseries.*, tvrage.releasetitle FROM userseries INNER JOIN tvrage ON tvrage.rageid = userseries.rageid WHERE userid = %d ORDER BY tvrage.releasetitle ASC", $uid));
	}

	public function delShow($uid, $rageid)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf("DELETE FROM userseries WHERE userid = %d AND rageid = %d", $uid, $rageid));
	}

	public function getShow($uid, $rageid)
	{
		$pdo = $this->pdo;
		return $pdo->queryOneRow(sprintf("SELECT userseries.*, tvr.releasetitle FROM userseries LEFT OUTER JOIN tvrage tvr ON tvr.rageid = userseries.rageid WHERE userseries.userid = %d AND userseries.rageid = %d", $uid, $rageid));
	}

	public function delShowForUser($uid)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf("DELETE FROM userseries WHERE userid = %d", $uid));
	}

	public function delShowForSeries($sid)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf("DELETE FROM userseries WHERE rageid = %d", $rid));
	}

	public function updateShow($uid, $rageid, $catid=array())
	{
		$pdo = $this->pdo;
		$catid = (!empty($catid)) ? $pdo->escapeString(implode('|', $catid)) : "NULL";
		$pdo->queryExec(sprintf("UPDATE userseries SET categoryid = %s WHERE userid = %d AND rageid = %d", $catid, $uid, $rageid));
	}
}
