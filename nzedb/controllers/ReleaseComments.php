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
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM releasecomment WHERE id = %d", $id));
	}

	public function getComments($id)
	{
		return $this->pdo->query(sprintf("SELECT releasecomment.* FROM releasecomment WHERE releaseid = %d", $id));
	}

	public function getCommentCount()
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM releasecomment"));
		return $res["num"];
	}

	// For deleting a single comment on the site.
	public function deleteComment($id)
	{
		$res = $this->getCommentById($id);
		if ($res)
		{
			$this->pdo->queryExec(sprintf("DELETE FROM releasecomment WHERE id = %d", $id));
			$this->updateReleaseCommentCount($res["releaseid"]);
		}
	}

	public function deleteCommentsForRelease($id)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM releasecomment WHERE releaseid = %d", $id));
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
				INSERT INTO releasecomment (releaseid, text, userid, createddate, host, username)
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
				SELECT releasecomment.*, releases.guid
				FROM releasecomment
				LEFT JOIN releases on releases.id = releasecomment.releaseid
				ORDER BY releasecomment.createddate DESC %s",
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
				SET comments = (SELECT COUNT(id) from releasecomment WHERE releasecomment.releaseid = %d)
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
				FROM releasecomment
				WHERE userid = %d",
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
				SELECT releasecomment.*
				FROM releasecomment
				WHERE userid = %d
				ORDER BY releasecomment.createddate DESC %s",
				$uid,
				$limit
			)
		);
	}
}
