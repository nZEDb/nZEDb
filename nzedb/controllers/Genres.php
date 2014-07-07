<?php

use nzedb\db\DB;

class Genres
{
	const CONSOLE_TYPE = Category::CAT_PARENT_GAME;
	const MUSIC_TYPE   = Category::CAT_PARENT_MUSIC;
	const GAME_TYPE    = Category::CAT_PARENT_PC;

	const STATUS_ENABLED = 0;
	const STATUS_DISABLED = 1;

	public function getGenres($type='', $activeonly=false)
	{
		$db = new DB();
		return $db->query($this->getListQuery($type, $activeonly));
	}

	private function getListQuery($type='', $activeonly=false)
	{
		if (!empty($type))
			$typesql = sprintf(" AND genres.type = %d", $type);
		else
			$typesql = '';

		if ($activeonly)
			$sql = sprintf("SELECT genres.* FROM genres INNER JOIN (SELECT DISTINCT genreid FROM musicinfo) x ON x.genreid = genres.id %s UNION SELECT genres.*  FROM genres INNER JOIN (SELECT DISTINCT genreid FROM consoleinfo) x ON x.genreid = genres.id %s ORDER BY title", $typesql, $typesql);
		else
			$sql = sprintf("SELECT genres.* FROM genres WHERE 1 %s ORDER BY title", $typesql);

		return $sql;
	}

	public function getRange($type='', $activeonly=false, $start, $num)
	{
		$db = new DB();
		$sql = $this->getListQuery($type, $activeonly);
		$sql .= " LIMIT ".$num." OFFSET ".$start;
		return $db->query($sql);
	}

	public function getCount($type='', $activeonly=false)
	{
		$db = new DB();

		if (!empty($type))
			$typesql = sprintf(" AND genres.type = %d", $type);
		else
			$typesql = '';

		if ($activeonly)
			$sql = sprintf("SELECT COUNT(*) AS num FROM genres INNER JOIN (SELECT DISTINCT genreid FROM musicinfo) x ON x.genreid = genres.id %s UNION SELECT COUNT(*) AS num FROM genres INNER JOIN (SELECT DISTINCT genreid FROM consoleinfo) y ON y.genreid = genres.id %s", $typesql, $typesql);
		else
			$sql = sprintf("SELECT COUNT(*) AS num FROM genres WHERE 1 %s ORDER BY title", $typesql);

		$res = $db->queryOneRow($sql);
		return $res["num"];
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM genres WHERE id = %d", $id));
	}

	public function update($id, $disabled)
	{
		$db = new DB();
		return $db->queryExec(sprintf("UPDATE genres SET disabled = %d WHERE id = %d", $disabled, $id));
	}

	public function getDisabledIDs()
	{
		$db = new DB();
		return $db->query("SELECT id FROM genres WHERE disabled = 1");
	}
}
