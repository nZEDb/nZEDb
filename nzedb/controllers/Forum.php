<?php

use nzedb\db\Settings;

class Forum
{
	/**
	 * @var Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	public function add($parentid, $userid, $subject, $message, $locked = 0, $sticky = 0, $replies = 0)
	{
		if ($message == "")
			return -1;

		if ($parentid != 0)
		{
			$par = $this->getParent($parentid);
			if ($par == false)
				return -1;

			$this->pdo->queryExec(sprintf("UPDATE forumpost SET replies = replies + 1, updateddate = NOW() WHERE id = %d", $parentid));
		}

		return $this->pdo->queryInsert(
			sprintf("
				INSERT INTO forumpost (forumid, parentid, userid, subject, message, locked, sticky, replies, createddate, updateddate)
				VALUES (1, %d, %d, %s, %s, %d, %d, %d, NOW(), NOW())",
				$parentid, $userid, $this->pdo->escapeString($subject), $this->pdo->escapeString($message), $locked, $sticky, $replies
			)
		);
	}

	public function getParent($parent)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT forumpost.*, users.username FROM forumpost LEFT OUTER JOIN users ON users.id = forumpost.userid WHERE forumpost.id = %d",
				$parent
			)
		);
	}

	public function getPosts($parent)
	{
		return $this->pdo->query(
			sprintf("
				SELECT forumpost.*, users.username
				FROM forumpost
				LEFT OUTER JOIN users ON users.id = forumpost.userid
				WHERE forumpost.id = %d OR parentid = %d
				ORDER BY createddate ASC
				LIMIT 250",
				$parent,
				$parent
			)
		);
	}

	public function getPost($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM forumpost WHERE id = %d", $id));
	}

	public function getBrowseCount()
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM forumpost WHERE parentid = 0"));
		return ($res === false ? 0 : $res["num"]);
	}

	public function getBrowseRange($start, $num)
	{
		return $this->pdo->query(
			sprintf("
				SELECT forumpost.*, users.username
				FROM forumpost
				LEFT OUTER JOIN users ON users.id = forumpost.userid
				WHERE parentid = 0
				ORDER BY updateddate DESC %s",
				($start === false ? '' : (" LIMIT " . $num . " OFFSET " . $start))
			)
		);
	}

	public function deleteParent($parent)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM forumpost WHERE id = %d OR parentid = %d", $parent, $parent));
	}

	public function deletePost($id)
	{
		$post = $this->getPost($id);
		if ($post) {
			if ($post["parentid"] == "0") {
				$this->deleteParent($id);
			} else {
				$this->pdo->queryExec(sprintf("DELETE FROM forumpost WHERE id = %d", $id));
			}
		}
	}

	public function deleteUser($id)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM forumpost WHERE userid = %d", $id));
	}

	public function getCountForUser($uid)
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM forumpost WHERE userid = %d", $uid));
		return ($res === false ? 0 :$res["num"]);
	}

	public function getForUserRange($uid, $start, $num)
	{
		return $this->pdo->query(
			sprintf("
				SELECT forumpost.*, users.username
				FROM forumpost
				LEFT OUTER JOIN users ON users.id = forumpost.userid
				WHERE userid = %d
				ORDER BY forumpost.createddate DESC %s",
				($start === false ? '' : (" LIMIT " . $num . " OFFSET " . $start)),
				$uid
			)
		);
	}
}
