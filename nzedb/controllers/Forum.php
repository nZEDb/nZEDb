<?php

use nzedb\db\Settings;

class Forum
{
		public function add($parentid, $userid, $subject, $message, $locked = 0, $sticky = 0, $replies = 0)
		{
			$pdo = new Settings();

			if ($message == "")
				return -1;

			if ($parentid != 0)
			{
				$par = $this->getParent($parentid);
				if ($par == false)
					return -1;

				$pdo->queryExec(sprintf("UPDATE forumpost SET replies = replies + 1, updateddate = NOW() WHERE id = %d", $parentid));
			}

			$pdo->queryInsert(sprintf("INSERT INTO forumpost (forumid, parentid, userid, subject, message, locked, sticky, replies, createddate, updateddate) VALUES (1, %d, %d, %s, %s, %d, %d, %d, NOW(), NOW())", $parentid, $userid, $pdo->escapeString($subject)
		, $pdo->escapeString($message), $locked, $sticky, $replies));

		}

		public function getParent($parent)
		{
			$pdo = new Settings();
			return $pdo->queryOneRow(sprintf("SELECT forumpost.*, users.username FROM forumpost LEFT OUTER JOIN users ON users.id = forumpost.userid WHERE forumpost.id = %d", $parent));
		}

		public function getPosts($parent)
		{
			$pdo = new Settings();
			return $pdo->query(sprintf("SELECT forumpost.*, users.username FROM forumpost LEFT OUTER JOIN users ON users.id = forumpost.userid WHERE forumpost.id = %d OR parentid = %d ORDER BY createddate ASC LIMIT 250", $parent, $parent));
		}

		public function getPost($id)
		{
			$pdo = new Settings();
			return $pdo->queryOneRow(sprintf("SELECT * FROM forumpost WHERE id = %d", $id));
		}

		public function getBrowseCount()
		{
			$pdo = new Settings();
			$res = $pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM forumpost WHERE parentid = 0"));
			return $res["num"];
		}

		public function getBrowseRange($start, $num)
		{
			$pdo = new Settings();

			if ($start === false)
				$limit = "";
			else
				$limit = " LIMIT ".$num." OFFSET ".$start;

			return $pdo->query("SELECT forumpost.*, users.username FROM forumpost LEFT OUTER JOIN users ON users.id = forumpost.userid WHERE parentid = 0 ORDER BY updateddate DESC".$limit);
		}

		public function deleteParent($parent)
		{
			$pdo = new Settings();
			$pdo->queryExec(sprintf("DELETE FROM forumpost WHERE id = %d OR parentid = %d", $parent, $parent));
		}

		public function deletePost($id)
		{
			$pdo = new Settings();
			$post = $this->getPost($id);
			if ($post)
			{
				if ($post["parentid"] == "0")
					$this->deleteParent($id);
				else
					$pdo->queryExec(sprintf("DELETE FROM forumpost WHERE id = %d", $id));
			}
		}

		public function deleteUser($id)
		{
			$pdo = new Settings();
			$pdo->queryExec(sprintf("DELETE FROM forumpost WHERE userid = %d", $id));
		}

		public function getCountForUser($uid)
		{
			$pdo = new Settings();
			$res = $pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM forumpost WHERE userid = %d", $uid));
			return $res["num"];
		}

		public function getForUserRange($uid, $start, $num)
		{
			$pdo = new Settings();

			if ($start === false)
				$limit = "";
			else
				$limit = " LIMIT ".$num." OFFSET ".$start;

			return $pdo->query(sprintf("SELECT forumpost.*, users.username FROM forumpost LEFT OUTER JOIN users ON users.id = forumpost.userid WHERE userid = %d ORDER BY forumpost.createddate DESC".$limit, $uid));
		}
}
