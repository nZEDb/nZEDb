<?php
require_once(WWW_DIR."/lib/framework/db.php");

class UserSeries
{

	public function addShow($uid, $rageid, $catid=array())
	{			
		$db = new DB();
		
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "null";
		
		$sql = sprintf("insert into userseries (userID, rageID, categoryID, createddate) values (%d, %d, %s, now())", $uid, $rageid, $catid);
		return $db->queryInsert($sql);		
	}	

	public function getShows($uid)
	{			
		$db = new DB();
		$sql = sprintf("select userseries.*, tvrage.releasetitle from userseries inner join tvrage on tvrage.rageID = userseries.rageID where userID = %d order by tvrage.releasetitle asc", $uid);
		return $db->query($sql);		
	}	

	public function delShow($uid, $rageid)
	{			
		$db = new DB();
		$db->query(sprintf("delete from userseries where userID = %d and rageID = %d ", $uid, $rageid));		
	}
	
	public function getShow($uid, $rageid)
	{			
		$db = new DB();
		$sql = sprintf("select userseries.*, tvr.releasetitle from userseries left outer join tvrage tvr on tvr.rageID = userseries.rageID where userseries.userID = %d and userseries.rageID = %d ", $uid, $rageid);
		return $db->queryOneRow($sql);		
	}		

	public function delShowForUser($uid)
	{			
		$db = new DB();
		$db->query(sprintf("delete from userseries where userID = %d", $uid));		
	}	

	public function delShowForSeries($sid)
	{			
		$db = new DB();
		$db->query(sprintf("delete from userseries where rageID = %d", $rid));		
	}
	
	public function updateShow($uid, $rageid, $catid=array())
	{			
		$db = new DB();
		
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "null";
		
		$sql = sprintf("update userseries set categoryID = %s where userID = %d and rageID = %d", $catid, $uid, $rageid);
		$db->query($sql);		
	}
}
?>