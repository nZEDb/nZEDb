<?php

class ReleaseFiles
{
	public function get($id)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM releasefiles WHERE releaseid = %d ORDER BY releasefiles.name", $id));
	}

	public function getByGuid($guid)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT releasefiles.* FROM releasefiles INNER JOIN releases r ON r.id = releasefiles.releaseid WHERE r.guid = %s ORDER BY releasefiles.name ", $db->escapeString($guid)));
	}

	public function delete($id)
	{
		$db = new DB();
		return $db->queryExec(sprintf("DELETE FROM releasefiles WHERE releaseid = %d", $id));
	}

	public function add($id, $name, $size, $createddate, $passworded)
	{
		$db = new DB();
		$ckname = $db->queryOneRow(sprintf('SELECT name FROM releasefiles WHERE name = %s', $db->escapeString(utf8_encode($name))));
		if (!isset($ckname['name']))
		{
			$sql = sprintf("INSERT INTO releasefiles (releaseid, name, size, createddate, passworded) VALUES (%d, %s, %s, %s, %d)", $id, $db->escapeString(utf8_encode($name)), $db->escapeString($size), $db->from_unixtime($createddate), $passworded );
			return $db->queryInsert($sql);
		}
	}
}
