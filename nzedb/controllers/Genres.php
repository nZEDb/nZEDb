<?php

use nzedb\db\Settings;

class Genres
{
	const CONSOLE_TYPE = Category::CAT_PARENT_GAME;
	const MUSIC_TYPE   = Category::CAT_PARENT_MUSIC;
	const GAME_TYPE    = Category::CAT_PARENT_PC;

	const STATUS_ENABLED = 0;
	const STATUS_DISABLED = 1;

	public function getGenres($type='', $activeonly=false)
	{
		$pdo = new Settings();
		return $pdo->query($this->getListQuery($type, $activeonly));
	}

	private function getListQuery($type='', $activeonly=false)
	{
		if (!empty($type))
			$typesql = sprintf(" AND genres.type = %d", $type);
		else
			$typesql = '';

		if ($activeonly)
			$sql = sprintf("SELECT genres.* FROM genres INNER JOIN (SELECT DISTINCT genreid FROM musicinfo) x ON x.genreid = genres.id %s UNION SELECT genres.*  FROM genres INNER JOIN (SELECT DISTINCT genreid FROM consoleinfo) x ON x.genreid = genres.id %s UNION SELECT genres.*  FROM genres INNER JOIN (SELECT DISTINCT genreid FROM gamesinfo) x ON x.genreid = genres.id %s ORDER BY title", $typesql, $typesql, $typesql);
		else
			$sql = sprintf("SELECT genres.* FROM genres WHERE 1 %s ORDER BY title", $typesql);

		return $sql;
	}

	public function getRange($type='', $activeonly=false, $start, $num)
	{
		$pdo = new Settings();
		$sql = $this->getListQuery($type, $activeonly);
		$sql .= " LIMIT ".$num." OFFSET ".$start;
		return $pdo->query($sql);
	}

	public function getCount($type='', $activeonly=false)
	{
		$pdo = new Settings();

		if (!empty($type))
			$typesql = sprintf(" AND genres.type = %d", $type);
		else
			$typesql = '';

		if ($activeonly)
			$sql = sprintf("SELECT COUNT(*) AS num FROM genres INNER JOIN (SELECT DISTINCT genreid FROM musicinfo) x ON x.genreid = genres.id %s UNION SELECT COUNT(*) AS num FROM genres INNER JOIN (SELECT DISTINCT genreid FROM consoleinfo) y ON y.genreid = genres.id %s", $typesql, $typesql);
		else
			$sql = sprintf("SELECT COUNT(*) AS num FROM genres WHERE 1 %s ORDER BY title", $typesql);

		$res = $pdo->queryOneRow($sql);
		return $res["num"];
	}

	public function getById($id)
	{
		$pdo = new Settings();
		return $pdo->queryOneRow(sprintf("SELECT * FROM genres WHERE id = %d", $id));
	}

	public function update($id, $disabled)
	{
		$pdo = new Settings();
		return $pdo->queryExec(sprintf("UPDATE genres SET disabled = %d WHERE id = %d", $disabled, $id));
	}

	public function getDisabledIDs()
	{
		$pdo = new Settings();
		return $pdo->query("SELECT id FROM genres WHERE disabled = 1");
	}
}
