<?php

require_once(WWW_DIR."/lib/framework/db.php");

class Forum
{
		public function add($parentid, $userid, $subject, $message, $locked = 0, $sticky = 0, $replies = 0)
		{
			$db = new DB();
			
			if ($message == "")
				return -1;
			
			if ($parentid != 0)
			{
				$par = $this->getParent($parentid);
				if ($par == false)
					return -1;
					
				$db->query(sprintf("update forumpost set replies = replies + 1, updateddate = now() where ID = %d", $parentid));		
			}
			
			$db->queryInsert(sprintf("INSERT INTO `forumpost`
            (             `forumID`,
             `parentID`,             `userID`,
             `subject`,             `message`,
             `locked`,             `sticky`,
             `replies`,             `createddate`,
             `updateddate`) VALUES (
        1,        %d,        %d,        %s,        %s,        %d,        %d,        %d,
        NOW(),        NOW())", $parentid, $userid, $db->escapeString($subject)	
        , $db->escapeString($message), $locked, $sticky, $replies));
			
		}

		public function getParent($parent)
		{
			$db = new DB();
			return $db->queryOneRow(sprintf(" SELECT forumpost.*, users.username from forumpost left outer join users on users.ID = forumpost.userID where forumpost.ID = %d ", $parent));		
		}

		public function getPosts($parent)
		{
			$db = new DB();
			return $db->query(sprintf(" SELECT forumpost.*, users.username from forumpost left outer join users on users.ID = forumpost.userID where forumpost.ID = %d or parentID = %d order by createddate asc limit 250", $parent, $parent));		
		}

		public function getPost($id)
		{
			$db = new DB();
			return $db->queryOneRow(sprintf(" SELECT * from forumpost where ID = %d", $id));		
		}

		public function getBrowseCount()
		{
			$db = new DB();
			$res = $db->queryOneRow(sprintf("select count(ID) as num from forumpost where parentID = 0"));		
			return $res["num"];
		}

		public function getBrowseRange($start, $num)
		{
			$db = new DB();

			if ($start === false)
				$limit = "";
			else
				$limit = " LIMIT ".$start.",".$num;

			return $db->query(sprintf(" SELECT forumpost.*, users.username from forumpost left outer join users on users.ID = forumpost.userID where parentID = 0 order by updateddate desc".$limit ));		
		}

		public function deleteParent($parent)
		{
			$db = new DB();
			$db->query(sprintf("delete from forumpost where ID = %d or parentID = %d", $parent, $parent));		
		}

		public function deletePost($id)
		{
			$db = new DB();
			$post = $this->getPost($id);
			if ($post)
			{
				if ($post["parentID"] == "0")
					$this->deleteParent($id);
				else
					$db->query(sprintf("delete from forumpost where ID = %d", $id));		
			}
		}

		public function deleteUser($id)
		{
			$db = new DB();
			$db->query(sprintf("delete from forumpost where userID = %d", $id));		
		}

		public function getCountForUser($uid)
		{			
			$db = new DB();
			$res = $db->queryOneRow(sprintf("select count(ID) as num from forumpost where userID = %d", $uid));		
			return $res["num"];
		}
		
		public function getForUserRange($uid, $start, $num)
		{		
			$db = new DB();
			
			if ($start === false)
				$limit = "";
			else
				$limit = " LIMIT ".$start.",".$num;
			
			return $db->query(sprintf(" SELECT forumpost.*, users.username FROM forumpost LEFT OUTER JOIN users ON users.ID = forumpost.userID where userID = %d order by forumpost.createddate desc ".$limit, $uid));		
		}

}
?>