<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/site.php");

class ReleaseComments
{	
	public function getCommentById($id)
	{			
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * from releasecomment where ID = %d", $id));		
	}
	
	public function getComments($id)
	{			
		$db = new DB();
		return $db->query(sprintf("SELECT releasecomment.*, users.username FROM releasecomment LEFT OUTER JOIN users ON users.ID = releasecomment.userID where releaseID = %d", $id));		
	}
	
	public function getCommentCount()
	{			
		$db = new DB();
		$res = $db->queryOneRow(sprintf("select count(ID) as num from releasecomment"));		
		return $res["num"];
	}

	public function deleteComment($id)
	{			
		$db = new DB();
		$res = $this->getCommentById($id);
		if ($res)
		{
			$db->query(sprintf("delete from releasecomment where ID = %d", $id));		
			$this->updateReleaseCommentCount($res["releaseID"]);
		}
	}
	
	public function deleteCommentsForRelease($id)
	{			
		$db = new DB();
		$db->query(sprintf("delete from releasecomment where releaseID = %d", $id));		
		$this->updateReleaseCommentCount($id);
	}
	
	public function deleteCommentsForUser($id)
	{			
		$db = new DB();
		
		$numcomments = $this->getCommentCountForUser($id);
		if ($numcomments > 0)
		{
			$comments = $this->getCommentsForUserRange($id, 0, $numcomments);
			foreach ($comments as $comment)
			{
				$this->deleteComment($comment["ID"]);
				$this->updateReleaseCommentCount($comment["releaseID"]);
			}
		}
	}

	public function addComment($id, $text, $userid, $host)
	{			
		$db = new DB();
		
		$site = new Sites();
		$s = $site->get();
		if ($s->storeuserips != "1")
			$host = "";		
		
		$comid = $db->queryInsert(sprintf("INSERT INTO releasecomment (`releaseID`, 	`text`, 	`userID`, 	`createddate`, 	`host`	)	
						VALUES (%d, 	%s, 	%d, 	now(), 	%s	)", $id, $db->escapeString($text), $userid, $db->escapeString($host) ));		
		$this->updateReleaseCommentCount($id);					
		return $comid;
	}
	
	public function getCommentsRange($start, $num)
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		return $db->query(" SELECT releasecomment.*, users.username, releases.guid FROM releasecomment LEFT OUTER JOIN users ON users.ID = releasecomment.userID inner join releases on releases.ID = releasecomment.releaseID order by releasecomment.createddate desc ".$limit);		
	}
	
	public function updateReleaseCommentCount($relid)
	{			
		$db = new DB();
		$db->query(sprintf("update releases
				set comments = (select count(ID) from releasecomment where releasecomment.releaseID = %d)
				where releases.ID = %d", $relid, $relid ));		
	}
	
	public function getCommentCountForUser($uid)
	{			
		$db = new DB();
		$res = $db->queryOneRow(sprintf("select count(ID) as num from releasecomment where userID = %d", $uid));		
		return $res["num"];
	}
	
	public function getCommentsForUserRange($uid, $start, $num)
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		return $db->query(sprintf(" SELECT releasecomment.*, users.username FROM releasecomment LEFT OUTER JOIN users ON users.ID = releasecomment.userID where userID = %d order by releasecomment.createddate desc ".$limit, $uid));		
	}
}
?>
