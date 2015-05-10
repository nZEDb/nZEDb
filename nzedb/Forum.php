<?php
namespace nzedb;

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
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	public function add($parentid, $userid, $subject, $message, $locked = 0, $sticky = 0, $replies = 0)
	{
		if ($message == "") {
			return -1;
		}

		if ($parentid != 0) {
			$par = $this->getParent($parentid);
			if ($par == false) {
				return -1;
			}

			$this->pdo->queryExec(sprintf("UPDATE forum_posts SET replies = replies + 1, updateddate = NOW() WHERE id = %d", $parentid));
		}

		return $this->pdo->queryInsert(
			sprintf("
				INSERT INTO forum_posts (forumid, parentid, user_id, subject, message, locked, sticky, replies, createddate, updateddate)
				VALUES (1, %d, %d, %s, %s, %d, %d, %d, NOW(), NOW())",
				$parentid, $userid, $this->pdo->escapeString($subject), $this->pdo->escapeString($message), $locked, $sticky, $replies
			)
		);
	}

	public function getParent($parent)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT forum_posts.*, users.username FROM forum_posts LEFT OUTER JOIN users ON users.id = forum_posts.user_id WHERE forum_posts.id = %d",
				$parent
			)
		);
	}

	public function getPosts($parent)
	{
		return $this->pdo->query(
			sprintf("
				SELECT forum_posts.*, users.username
				FROM forum_posts
				LEFT OUTER JOIN users ON users.id = forum_posts.user_id
				WHERE forum_posts.id = %d OR parentid = %d
				ORDER BY createddate ASC
				LIMIT 250",
				$parent,
				$parent
			)
		);
	}

	public function getPost($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM forum_posts WHERE id = %d", $id));
	}

	public function getBrowseCount()
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM forum_posts WHERE parentid = 0"));
		return ($res === false ? 0 : $res["num"]);
	}

	public function getBrowseRange($start, $num)
	{
		return $this->pdo->query(
			sprintf("
				SELECT forum_posts.*, users.username
				FROM forum_posts
				LEFT OUTER JOIN users ON users.id = forum_posts.user_id
				WHERE parentid = 0
				ORDER BY updateddate DESC %s",
				($start === false ? '' : (" LIMIT " . $num . " OFFSET " . $start))
			)
		);
	}

	public function deleteParent($parent)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM forum_posts WHERE id = %d OR parentid = %d", $parent, $parent));
	}

	public function deletePost($id)
	{
		$post = $this->getPost($id);
		if ($post) {
			if ($post["parentid"] == "0") {
				$this->deleteParent($id);
			} else {
				$this->pdo->queryExec(sprintf("DELETE FROM forum_posts WHERE id = %d", $id));
			}
		}
	}

	public function deleteUser($id)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM forum_posts WHERE user_id = %d", $id));
	}

	public function getCountForUser($uid)
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM forum_posts WHERE user_id = %d", $uid));
		return ($res === false ? 0 : $res["num"]);
	}

	public function getForUserRange($uid, $start, $num)
	{
		return $this->pdo->query(
			sprintf("
				SELECT forum_posts.*, users.username
				FROM forum_posts
				LEFT OUTER JOIN users ON users.id = forum_posts.user_id
				WHERE user_id = %d
				ORDER BY forum_posts.createddate DESC %s",
				($start === false ? '' : (" LIMIT " . $num . " OFFSET " . $start)),
				$uid
			)
		);
	}
}
