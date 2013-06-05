<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/category.php");

class Genres 
{
	const CONSOLE_TYPE = Category::CAT_PARENT_GAME;
	const MUSIC_TYPE = Category::CAT_PARENT_MUSIC;

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
			$typesql = sprintf(" and genres.type = %d", $type);
		else
			$typesql = '';
		
		if ($activeonly)
		{
			$sql = sprintf("SELECT genres.*  FROM genres INNER JOIN (SELECT DISTINCT genreID FROM musicinfo) X ON X.genreID = genres.ID %s
			UNION
			SELECT genres.*  FROM genres INNER JOIN (SELECT DISTINCT genreID FROM consoleinfo) X ON X.genreID = genres.ID %s
			ORDER BY title", $typesql, $typesql);
		}
		else
			$sql = sprintf("select genres.* from genres where 1 %s order by title", $typesql);		
			
		return $sql;
	}

	public function getRange($type='', $activeonly=false, $start, $num)
	{
		$db = new DB();
		$sql = $this->getListQuery($type, $activeonly);

		$limit = " LIMIT ".$start.",".$num;
		$sql = $sql.$limit;

		return $db->query($sql);

	}

	public function getCount($type='', $activeonly=false)
	{
		$db = new DB();
		
		if (!empty($type))
			$typesql = sprintf(" and genres.type = %d", $type);
		else
			$typesql = '';
		
		if ($activeonly)
		{
			$sql = sprintf("SELECT count(*) as num  FROM genres INNER JOIN (SELECT DISTINCT genreID FROM musicinfo) X ON X.genreID = genres.ID %s
			UNION
			SELECT genres.*  FROM genres INNER JOIN (SELECT DISTINCT genreID FROM consoleinfo) X ON X.genreID = genres.ID %s
			ORDER BY title", $typesql, $typesql);
		}
		else
			$sql = sprintf("select count(*) as num from genres where 1 %s order by title", $typesql);		
			
		$res = $db->queryOneRow($sql);
		return $res["num"];
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * from genres where ID = %d", $id));
	}

	public function update($id, $disabled)
	{
		$db = new DB();
		return $db->query(sprintf("update genres set disabled = %d where ID = %d", $disabled, $id));
	}

	public function getDisabledIDs()
	{
		$db = new DB();
		return $db->queryDirect("SELECT ID from genres where disabled = 1");
	}
}
?>
