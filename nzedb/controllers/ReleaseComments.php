<?php

use nzedb\db\Settings;

class ReleaseComments
{
	// Returns the row associated to the id of a comment.
	public function getCommentById($id)
	{
		$pdo = new Settings();
		return $pdo->queryOneRow(sprintf("SELECT * FROM releasecomment WHERE id = %d", $id));
	}

	public function getComments($id)
	{
		$pdo = new Settings();
		return $pdo->query(sprintf("SELECT releasecomment.* FROM releasecomment WHERE releaseid = %d", $id));
	}

	public function getCommentCount()
	{
		$pdo = new Settings();
		$res = $pdo->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM releasecomment"));
		return $res["num"];
	}

	// For deleting a single comment on the site.
	public function deleteComment($id)
	{
		$pdo = new Settings();
		$res = $this->getCommentById($id);
		if ($res)
		{
			$pdo->queryExec(sprintf("DELETE FROM releasecomment WHERE id = %d", $id));
			$this->updateReleaseCommentCount($res["releaseid"]);
		}
	}

	public function deleteCommentsForRelease($id)
	{
		$pdo = new Settings();
		$pdo->queryExec(sprintf("DELETE FROM releasecomment WHERE releaseid = %d", $id));
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
		$pdo = new Settings();

		if ($pdo->getSetting('storeuserips') != "1")
			$host = "";

		$username = $pdo->queryOneRow(sprintf('SELECT username FROM users WHERE id = %d', $userid));
		$username = ($username === false ? 'ANON' : $username['username']);

		$comid = $pdo->queryInsert(
			sprintf("
				INSERT INTO releasecomment (releaseid, text, userid, createddate, host, username)
				VALUES (%d, %s, %d, NOW(), %s, %s)",
				$id,
				$pdo->escapeString($text),
				$userid,
				$pdo->escapeString($host),
				$pdo->escapeString($username)
			)
		);
		$this->updateReleaseCommentCount($id);
		return $comid;
	}

	public function getCommentsRange($start, $num)
	{
		$pdo = new Settings();
		return $pdo->query(
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
		$pdo = new Settings();
		$pdo->queryExec(
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
		$pdo = new Settings();
		$res = $pdo->queryOneRow(
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
		$pdo = new Settings();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		return $pdo->query(
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
