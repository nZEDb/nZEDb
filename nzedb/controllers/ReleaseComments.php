<?php

use nzedb\db\Settings;

class ReleaseComments
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param nzedb\db\Settings $settings
	 */
	public function __construct($settings = null)
	{
		$this->pdo = ($settings instanceof Settings ? $settings : new Settings());
	}

	// Returns the row associated to the id of a comment.
	public function getCommentById($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM release_comments WHERE id = %d", $id));
	}

	public function getComments($id)
	{
		return $this->pdo->query(sprintf("SELECT release_comments.* FROM release_comments WHERE releaseid = %d", $id));
	}

	public function getCommentCount()
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM release_comments"));
		return $res["num"];
	}

	// For deleting a single comment on the site.
	public function deleteComment($id)
	{
		$res = $this->getCommentById($id);
		if ($res)
		{
			$this->pdo->queryExec(sprintf("DELETE FROM release_comments WHERE id = %d", $id));
			$this->updateReleaseCommentCount($res["releaseid"]);
		}
	}

	public function deleteCommentsForRelease($id)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM release_comments WHERE releaseid = %d", $id));
		$this->updateReleaseCommentCount($id);
	}

	public function deleteCommentsForUser($id)
	{

		$numcomments = $this->getCommentCountForUser($id);
		if ($numcomments > 0)
		{
			$comments = $this->getCommentsForUserRange($id, 0, $numcomments);
			foreach ($comments as $comment)
			{
				$this->deleteComment($comment["id"]);
				$this->updateReleaseCommentCount($comment["releaseid"]);
			}
		}
	}

	public function addComment($id, $text, $userid, $host)
	{
		if ($this->pdo->getSetting('storeuserips') != "1")
			$host = "";

		$username = $this->pdo->queryOneRow(sprintf('SELECT username FROM users WHERE id = %d', $userid));
		$username = ($username === false ? 'ANON' : $username['username']);

		$comid = $this->pdo->queryInsert(
			sprintf("
				INSERT INTO release_comments (releaseid, text, user_id, createddate, host, username)
				VALUES (%d, %s, %d, NOW(), %s, %s)",
				$id,
				$this->pdo->escapeString($text),
				$userid,
				$this->pdo->escapeString($host),
				$this->pdo->escapeString($username)
			)
		);
		$this->updateReleaseCommentCount($id);
		return $comid;
	}

	public function getCommentsRange($start, $num)
	{
		return $this->pdo->query(
			sprintf("
				SELECT release_comments.*, releases.guid
				FROM release_comments
				LEFT JOIN releases on releases.id = release_comments.releaseid
				ORDER BY release_comments.createddate DESC %s",
				($start === false ? '' : " LIMIT " . $num . " OFFSET " . $start)
			)
		);
	}

	// Updates the amount of comments for the rlease.
	public function updateReleaseCommentCount($relid)
	{
		$this->pdo->queryExec(
			sprintf("
				UPDATE releases
				SET comments = (SELECT COUNT(id) from release_comments WHERE release_comments.releaseid = %d)
				WHERE releases.id = %d",
				$relid,
				$relid
			)
		);
	}

	public function getCommentCountForUser($uid)
	{
		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(id) AS num
				FROM release_comments
				WHERE user_id = %d",
				$uid
			)
		);
		return $res["num"];
	}

	public function getCommentsForUserRange($uid, $start, $num)
	{
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		return $this->pdo->query(
			sprintf("
				SELECT release_comments.*
				FROM release_comments
				WHERE user_id = %d
				ORDER BY release_comments.createddate DESC %s",
				$uid,
				$limit
			)
		);
	}
}
