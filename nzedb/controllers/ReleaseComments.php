<?php

use nzedb\db\DB;

class ReleaseComments
{
	// Returns the row associated to the id of a comment.
	public function getCommentById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM releasecomment WHERE id = %d", $id));
	}

	public function getComments($id)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT releasecomment.*, users.username FROM releasecomment LEFT OUTER JOIN users ON users.id = releasecomment.userid WHERE releaseid = %d", $id));
	}

	public function getCommentCount()
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM releasecomment"));
		return $res["num"];
	}

	// For deleting a single comment on the site.
	public function deleteComment($id)
	{
		$db = new DB();
		$res = $this->getCommentById($id);
		if ($res)
		{
			$db->queryExec(sprintf("DELETE FROM releasecomment WHERE id = %d", $id));
			$this->updateReleaseCommentCount($res["releaseid"]);
		}
	}

	public function deleteCommentsForRelease($id)
	{
		$db = new DB();
		$db->queryExec(sprintf("DELETE FROM releasecomment WHERE releaseid = %d", $id));
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
				$this->deleteComment($comment["id"]);
				$this->updateReleaseCommentCount($comment["releaseid"]);
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

		$comid = $db->queryInsert(sprintf("INSERT INTO releasecomment (releaseid, text, userid, createddate, host) VALUES (%d, %s, %d, NOW(), %s)", $id, $db->escapeString($text), $userid, $db->escapeString($host)));
		$this->updateReleaseCommentCount($id);
		return $comid;
	}

	public function getCommentsRange($start, $num)
	{
		$db = new DB();

		return $db->query(
			sprintf("
				SELECT releasecomment.*, users.username, releases.guid
				FROM releasecomment
				LEFT OUTER JOIN users ON users.id = releasecomment.userid
				LEFT JOIN releases on releases.id = releasecomment.releaseid
				ORDER BY releasecomment.createddate DESC %s",
				($start === false ? '' : " LIMIT " . $num . " OFFSET " . $start)));
	}

	// Updates the amount of comments for the rlease.
	public function updateReleaseCommentCount($relid)
	{
		$db = new DB();
		$db->queryExec(sprintf("UPDATE releases SET comments = (SELECT COUNT(id) from releasecomment WHERE releasecomment.releaseid = %d) WHERE releases.id = %d", $relid, $relid));
	}

	public function getCommentCountForUser($uid)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM releasecomment WHERE userid = %d", $uid));
		return $res["num"];
	}

	public function getCommentsForUserRange($uid, $start, $num)
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		return $db->query(sprintf("SELECT releasecomment.*, users.username FROM releasecomment LEFT OUTER JOIN users ON users.id = releasecomment.userid WHERE userid = %d ORDER BY releasecomment.createddate DESC".$limit, $uid));
	}
}
