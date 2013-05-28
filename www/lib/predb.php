<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/groups.php");
/*
 * Class for inserting names/categories/md5 etc from predb sources into the DB, also for matching names on files / subjects.
 */

Class Predb
{
	function Predb($echooutput=false)
	{
		$this->echooutput = $echooutput;
	}
	
	// Retrieve pre info from predb sources and store them in the DB.
	// Returns the quantity of new titles retrieved.
	public function combinePre()
	{
		$db = new DB;
		$newnames = 0;
		$newestrel = $db->queryOneRow("SELECT adddate, ID FROM predb ORDER BY adddate DESC LIMIT 1");
		if (strtotime($newestrel["adddate"]) < time()-600)
		{
			$newwomble = $this->retrieveWomble();
			$newzenet = $this->retrieveZenet();
			$newprelist = $this->retrievePrelist();
			$neworly = $this->retrieveOrlydb();
			$newsrr = $this->retrieveSrr();
			$newpdme = $this->retrievePredbme();
			$newnames = $newwomble+$newzenet+$newprelist+$neworly+$newsrr+$newpdme;
			if ($newnames == 0)
				$db->query(sprintf("UPDATE predb SET adddate = now() where ID = %d", $newestrel["ID"]));
		}
		return $newnames;
	}
	
	public function retrieveWomble()
	{
		$db = new DB;
		$newnames = 0;

		$buffer = getUrl("http://nzb.isasecret.com/");
		if ($buffer !== false && strlen($buffer))
		{
			$ret = array();
			if (preg_match_all('/<tr bgcolor=#[df]{6}>.+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<tr bgcolor=#[df]{6}>.+?<td>(?P<date>.+?)<\/td>(.+?right>(?P<size1>.+?)&nbsp;(?P<size2>.+?)<\/td.+?)?<td>(?P<category>.+?)<\/td.+?<a href=.+?(<a href="(?P<nfo>.+?)">nfo<\/a>.+)?<td>(?P<title>.+?)<\/td.+tr>/s', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"])
								continue;
							else
							{
								if (!isset($matches2["size1"]) && empty($matches["size1"]))
									$size = "NULL";
								else
									$size = $db->escapeString($matches2["size1"].$matches2["size2"]);
								
								if ($matches2["nfo"] == "")
									$nfo = "NULL";
								else
									$nfo = $db->escapeString("http://nzb.isasecret.com/".$matches2["nfo"]);
								
								$db->query(sprintf("INSERT INTO predb (title, nfo, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $nfo, $size, $db->escapeString($matches2["category"]), $db->escapeString("womble"), $db->escapeString(md5($matches2["title"]))));
								$newnames++;
							}	
						}
					}
				}
			}
		}
		return $newnames;
	}
	
	public function retrieveZenet()
	{
		$db = new DB;
		$newnames = 0;

		$buffer = getUrl("http://pre.zenet.org/live.php");
		if ($buffer !== false && strlen($buffer))
		{
			$ret = array();
			if (preg_match_all('/<tr bgcolor=".+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<tr bgcolor=".+?<td.+?">(?P<date>.+?)<\/td.+?<td.+?(<font.+?">(?P<category>.+?)<\/a.+?|">(?P<category1>NUKE)+?)?<\/td.+?<td.+?">(?P<title>.+?)-<a.+?<\/td.+?<td.+<td.+?(">(?P<size1>[\d.]+)<b>(?P<size2>.+?)<\/b>.+)?<\/tr>/s', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"])
								continue;
							else
							{
								if (!isset($matches2["size1"]) && empty($matches["size1"]))
									$size = "NULL";
								else
									$size = $db->escapeString(round($matches2["size1"]).$matches2["size2"]);
								
								if (isset($matches2["category"]) && !empty($matches2["category"]))
									$category = $db->escapeString($matches2["category"]);
								else if (isset($matches2["category1"]) && !empty($matches2["category1"]))
									$category = $db->escapeString($matches2["category1"]);
								else
									$category = "NULL";
								
								$db->query(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $category, $db->escapeString("zenet"), $db->escapeString(md5($matches2["title"]))));
								$newnames++;
							}	
						}
					}
				}
			}
		}
		return $newnames;
	}
	
	public function retrievePrelist()
	{
		$db = new DB;
		$newnames = 0;

		$buffer = getUrl("http://www.prelist.ws/");
		if ($buffer !== false && strlen($buffer))
		{
			$ret = array();
			if (preg_match_all('/<small><span.+?<\/span><\/small>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (!preg_match('/NUKED/', $m) && preg_match('/">\[ (?P<date>.+?) U.+?">(?P<category>.+?)<\/a>.+?">(?P<title>.+?)<\/a>.+?(b>\[ (?P<size>.+?) \]<\/b)?/si', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"])
								continue;
							else
							{
								if (!isset($matches2["size"]) && empty($matches["size"]))
									$size = "NULL";
								else
									$size = $db->escapeString(round($matches2["size"]));
								
								$db->query(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->escapeString("prelist"), $db->escapeString(md5($matches2["title"]))));
								$newnames++;
							}
						}
						else if (preg_match('/">\[ (?P<date>.+?) U.+?">(?P<category>.+?)<\/a>.+?">(?P<category1>.+?)<\/a.+">(?P<title>.+?)<\/a>/si', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"])
								continue;
							else
							{
								$category = $db->escapeString($matches2["category"].", ".$matches2["category1"]);
								
								$db->query(sprintf("INSERT INTO predb (title, category, predate, adddate, source, md5) VALUES (%s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $category, $db->escapeString("prelist"), $db->escapeString(md5($matches2["title"]))));
								$newnames++;
							}
						}
					}
				}
			}
		}
		return $newnames;
	}
	
	public function retrieveOrlydb()
	{
		$db = new DB;
		$newnames = 0;

		$buffer = getUrl("http://www.orlydb.com/");
		if ($buffer !== false && strlen($buffer))
		{
			$ret = array();
			if (preg_match('/<div id="releases">(.+)<div id="pager">/s', $buffer, $match))
			{
				if (preg_match_all('/<div>.+<\/div>/s', $match["1"], $matches))
				{
					foreach ($matches as $m1)
					{
						foreach ($m1 as $m)
						{
							if (preg_match('/timestamp">(?P<date>.+?)<\/span>.+?section">.+?">(?P<category>.+?)<\/a>.+?release">(?P<title>.+?)<\/span>(.+info">(?P<size>.+?) )?/s', $m, $matches2))
							{
								$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
								if ($oldname["title"] == $matches2["title"])
									continue;
								else
								{
									if (!isset($matches2["size"]) && empty($matches["size"]))
										$size = "NULL";
									else
										$size = $db->escapeString($matches2["size"]);
								
									$db->query(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->escapeString("orlydb"), $db->escapeString(md5($matches2["title"]))));
									$newnames++;
								}
							}
						}
					}
				}
			}
		}
		return $newnames;
	}
	
	public function retrieveSrr()
	{
		$db = new DB;
		$newnames = 0;
		$releases = @simplexml_load_file('http://www.srrdb.com/feed/srrs');
		if ($releases !== false)
		{
			foreach ($releases->channel->item as $release)
			{
				$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($release->title)));
				if ($oldname["title"] == $release->title)
					continue;
				else
				{
					$db->query(sprintf("INSERT INTO predb (title, predate, adddate, source, md5) VALUES (%s, FROM_UNIXTIME(".strtotime($release->pubDate)."), now(), %s, %s)", $db->escapeString($release->title), $db->escapeString("srrdb"), $db->escapeString(md5($release->title))));
					$newnames++;
				}
			}
		}
		return $newnames;
	}
	
	public function retrievePredbme()
	{
		$db = new DB;
		$newnames = 0;
		$releases = @simplexml_load_file('http://predb.me/?rss');
		if ($releases !== false)
		{
			foreach ($releases->channel->item as $release)
			{
				$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($release->title)));
				if ($oldname["title"] == $release->title)
					continue;
				else
				{
					$db->query(sprintf("INSERT INTO predb (title, predate, adddate, source, md5) VALUES (%s, now(), now(), %s, %s)", $db->escapeString($release->title), $db->escapeString("predbme"), $db->escapeString(md5($release->title))));
					$newnames++;
				}
			}
		}
		return $newnames;
	}
	
	// Matches the names within the predb table to release files and subjects (names). In the future, use the MD5.
	public function parseTitles($time, $echo, $cats, $namestatus)
	{
		$db = new DB();
		$updated = 0;
		
		if($this->echooutput)
		{
			$te = "";
			if ($time == 1)
				$te = " in the past 3 hours";
			echo "Fixing search names".$te." using the predb titles.\n";
		}
		
		$tq = "";
		if ($time == 1)
			$tq = " and r.adddate > (now() - interval 3 hour)";
		$ct = "";
		if ($cats == 1)
			$ct = " and (r.categoryID like \"1090\" or r.categoryID like \"2020\" or r.categoryID like \"3050\" or r.categoryID like \"6050\" or r.categoryID like \"5050\" or r.categoryID like \"7010\" or r.categoryID like \"8050\")";
		
		if($res = $db->queryDirect("SELECT r.searchname, r.categoryID, r.groupID, p.source, p.title, r.ID from releases r left join releasefiles rf on rf.releaseID = r.ID, predb p where (r.name like concat('%', p.title, '%') or rf.name like concat('%', p.title, '%')) and r.relnamestatus < 2".$tq.$ct))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				if ($row["title"] !== $row["searchname"])
				{
					$category = new Category();
					$determinedcat = $category->determineCategory($row["title"], $row["groupID"]);
					
					if ($echo == 1)
					{
						if ($namestatus == 1)
							$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 2 where ID = %d", $db->escapeString($row["title"]), $determinedcat, $row["ID"]));
						else
							$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d where ID = %d", $db->escapeString($row["title"]), $determinedcat, $row["ID"]));
					}
					if ($this->echooutput)
					{
						$groups = new Groups();
						
						echo"New name: ".$row["title"]."\n".
							"Old name: ".$row["searchname"]."\n".
							"New cat:  ".$category->getNameByID($determinedcat)."\n".
							"Old cat:  ".$category->getNameByID($row["categoryID"])."\n".
							"Group:    ".$groups->getByNameByID($row["groupID"])."\n".
							"Method:   "."predb: ".$row["source"]."\n"."\n";
					}
				}
				$updated++;
			}
		}
		return $updated;
	}
	
	public function getAll()
	{			
		$db = new DB();
		return $db->query("SELECT * FROM predb ORDER BY adddate DESC");
	}
}
?>
