<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/namefixer.php");
require_once(WWW_DIR."lib/site.php");

/*
 * Class for inserting names/categories/md5 etc from predb sources into the DB, also for matching names on files / subjects.
 */

Class Predb
{
	function Predb($echooutput=false)
	{
		$s = new Sites();
		$this->site = $s->get();
		$this->echooutput = $echooutput;
	}

	// Retrieve pre info from predb sources and store them in the DB.
	// Returns the quantity of new titles retrieved.
	public function combinePre()
	{
		$db = new DB();
		$newnames = 0;
		$newestrel = $db->queryOneRow("SELECT adddate, ID FROM predb ORDER BY adddate DESC LIMIT 1");
		if (strtotime($newestrel["adddate"]) < time()-900)
		{
			if ($this->echooutput)
				echo "Retrieving titles from preDB sources.\n";
			$newwomble = $this->retrieveWomble();
			$newomgwtf = $this->retrieveOmgwtfnzbs();
			$newzenet = $this->retrieveZenet();
			$newprelist = $this->retrievePrelist();
			$neworly = $this->retrieveOrlydb();
			$newsrr = $this->retrieveSrr();
			$newpdme = $this->retrievePredbme();
			$newnames = $newwomble+$newomgwtf+$newzenet+$newprelist+$neworly+$newsrr+$newpdme;
			if ($newnames == 0)
				$db->query(sprintf("UPDATE predb SET adddate = now() where ID = %d", $newestrel["ID"]));
		}
		$matched = $this->matchPredb();
		if ($matched > 0 && $this->echooutput)
			echo "\nMatched ".$matched." predDB titles to release search names.\n";
		$nfos = $this->matchNfo();
		if ($nfos > 0 && $this->echooutput)
			echo "\nAdded ".$nfos." missing NFOs from preDB sources.\n";
		return $newnames;
	}

	public function retrieveWomble()
	{
		$db = new DB();
		$newnames = 0;

		$buffer = getUrl("http://www.newshost.co.za");
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<tr bgcolor=#[df]{6}>.+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<tr bgcolor=#[df]{6}>.+?<td>(?P<date>.+?)<\/td>(.+?right>(?P<size1>.+?)&nbsp;(?P<size2>.+?)<\/td.+?)?<td>(?P<category>.+?)<\/td.+?<a href=.+?(<a href="(?P<nfo>.+?)">nfo<\/a>.+)?<td>(?P<title>.+?)<\/td.+tr>/s', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title, source, ID FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"])
							{
								if ($oldname["source"] == "womble")
									continue;
								else
								{
									if (!isset($matches2["size1"]) && empty($matches2["size1"]))
										$size = "NULL";
									else
										$size = $db->escapeString($matches2["size1"].$matches2["size2"]);

									if ($matches2["nfo"] == "")
										$nfo = "NULL";
									else
										$nfo = $db->escapeString("http://nzb.isasecret.com/".$matches2["nfo"]);

									$db->query(sprintf("UPDATE predb SET nfo = %s, size = %s, category = %s, predate = FROM_UNIXTIME(".strtotime($matches2["date"])."), adddate = now(), source = %s where ID = %d", $nfo, $size, $db->escapeString($matches2["category"]), $db->escapeString("womble"), $oldname["ID"]));
									$newnames++;
								}
							}
							else
							{
								if (!isset($matches2["size1"]) && empty($matches2["size1"]))
									$size = "NULL";
								else
									$size = $db->escapeString($matches2["size1"].$matches2["size2"]);

								if ($matches2["nfo"] == "")
									$nfo = "NULL";
								else
									$nfo = $db->escapeString("http://nzb.isasecret.com/".$matches2["nfo"]);

								$db->query(sprintf("INSERT IGNORE INTO predb (title, nfo, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $nfo, $size, $db->escapeString($matches2["category"]), $db->escapeString("womble"), $db->escapeString(md5($matches2["title"]))));
								$newnames++;
							}
						}
					}
				}
			}
		}
		return $newnames;
	}

	public function retrieveOmgwtfnzbs()
	{
		$db = new DB();
		$newnames = 0;

		$buffer = getUrl("http://rss.omgwtfnzbs.org/rss-info.php");
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<item>.+?<\/item>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<title>(?P<title>.+?)<\/title.+?pubDate>(?P<date>.+?)<\/pubDate.+?gory:<\/b> (?P<category>.+?)<br \/.+?<\/b> (?P<size1>.+?) (?P<size2>[a-zA-Z]+)<b/s', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title, source, ID FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"])
							{
								if ($oldname["source"] == "womble")
								{
									continue;
								}
								else
								{
									$size = $db->escapeString(round($matches2["size1"]).$matches2["size2"]);
									$db->query(sprintf("UPDATE predb SET size = %s, category = %s, predate = FROM_UNIXTIME(".strtotime($matches2["date"])."), adddate = now(), source = %s where ID = %d", $size, $db->escapeString($matches2["category"]), $db->escapeString("omgwtfnzbs"), $oldname["ID"]));
									$newnames++;
								}
							}
							else
							{
								$size = $db->escapeString(round($matches2["size1"]).$matches2["size2"]);
								$title = preg_replace("/  - omgwtfnzbs.org/", "", $matches2["title"]);
								$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($title), $size, $db->escapeString($matches2["category"]), $db->escapeString("omgwtfnzbs"), $db->escapeString(md5($matches2["title"]))));
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
		$db = new DB();
		$newnames = 0;

		$buffer = getUrl("http://pre.zenet.org/live.php");
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<tr bgcolor=".+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<tr bgcolor=".+?<td.+?">(?P<date>.+?)<\/td.+?<td.+?(<font.+?">(?P<category>.+?)<\/a.+?|">(?P<category1>NUKE)+?)?<\/td.+?<td.+?">(?P<title>.+?-)<a.+?<b>(?P<title2>.+?)<\/b>.+?<\/td.+?<td.+<td.+?(">(?P<size1>[\d.]+)<b>(?P<size2>.+?)<\/b>.+)?<\/tr>/s', $m, $matches2))
						{
							$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($matches2["title"])));
							if ($oldname["title"] == $matches2["title"].$matches2["title2"])
								continue;
							else
							{
								if (!isset($matches2["size1"]) && empty($matches2["size1"]))
									$size = "NULL";
								else
									$size = $db->escapeString(round($matches2["size1"]).$matches2["size2"]);

								if (isset($matches2["category"]) && !empty($matches2["category"]))
									$category = $db->escapeString($matches2["category"]);
								else if (isset($matches2["category1"]) && !empty($matches2["category1"]))
									$category = $db->escapeString($matches2["category1"]);
								else
									$category = "NULL";

								$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"].$matches2["title2"]), $size, $category, $db->escapeString("zenet"), $db->escapeString(md5($matches2["title"].$matches2["title2"]))));
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
		$db = new DB();
		$newnames = 0;

		$buffer = getUrl("http://www.prelist.ws/");
		if ($buffer !== false && strlen($buffer))
		{
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
								if (!isset($matches2["size"]) && empty($matches2["size"]))
									$size = "NULL";
								else
									$size = $db->escapeString(round($matches2["size"]));

								$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->escapeString("prelist"), $db->escapeString(md5($matches2["title"]))));
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

								$db->query(sprintf("INSERT IGNORE INTO predb (title, category, predate, adddate, source, md5) VALUES (%s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $category, $db->escapeString("prelist"), $db->escapeString(md5($matches2["title"]))));
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
		$db = new DB();
		$newnames = 0;

		$buffer = getUrl("http://www.orlydb.com/");
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match('/<div id="releases">(.+)<div id="pager">/s', $buffer, $match))
			{
				if (preg_match_all('/<div>.+?<\/div>/s', $match["1"], $matches))
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
									if (!isset($matches2["size"]) && empty($matches2["size"]))
										$size = "NULL";
									else
										$size = $db->escapeString($matches2["size"]);

									$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->escapeString("orlydb"), $db->escapeString(md5($matches2["title"]))));
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
		$db = new DB();
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
					$db->query(sprintf("INSERT IGNORE INTO predb (title, predate, adddate, source, md5) VALUES (%s, FROM_UNIXTIME(".strtotime($release->pubDate)."), now(), %s, %s)", $db->escapeString($release->title), $db->escapeString("srrdb"), $db->escapeString(md5($release->title))));
					$newnames++;
				}
			}
		}
		return $newnames;
	}

	public function retrievePredbme()
	{
		$db = new DB();
		$newnames = 0;
		$arr = array("http://predb.me/?cats=movies-sd&rss=1", "http://predb.me/?cats=movies-hd&rss=1", "http://predb.me/?cats=movies-discs&rss=1", "http://predb.me/?cats=tv-sd&rss=1", "http://predb.me/?cats=tv-hd&rss=1", "http://predb.me/?cats=tv-discs&rss=1", "http://predb.me/?cats=music-audio&rss=1", "http://predb.me/?cats=music-video&rss=1", "http://predb.me/?cats=music-discs&rss=1", "http://predb.me/?cats=games-pc&rss=1", "http://predb.me/?cats=games-xbox&rss=1", "http://predb.me/?cats=games-playstation&rss=1", "http://predb.me/?cats=games-nintendo&rss=1", "http://predb.me/?cats=apps-windows&rss=1", "http://predb.me/?cats=apps-linux&rss=1", "http://predb.me/?cats=apps-mac&rss=1", "http://predb.me/?cats=apps-mobile&rss=1", "http://predb.me/?cats=books-ebooks&rss=1", "http://predb.me/?cats=books-audio-books&rss=1", "http://predb.me/?cats=xxx-videos&rss=1", "http://predb.me/?cats=xxx-images&rss=1", "http://predb.me/?cats=dox&rss=1", "http://predb.me/?cats=unknown&rss=1");
		foreach ($arr as &$value)
		{
			$releases = @simplexml_load_file($value);
			if ($releases !== false)
			{
				foreach ($releases->channel->item as $release)
				{
					$oldname = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE title = %s", $db->escapeString($release->title)));
					if ($oldname["title"] == $release->title)
						continue;
					else
					{
						$db->query(sprintf("INSERT IGNORE INTO predb (title, predate, adddate, source, md5) VALUES (%s, now(), now(), %s, %s)", $db->escapeString($release->title), $db->escapeString("predbme"), $db->escapeString(md5($release->title))));
						$newnames++;
					}
				}
			}
		}
		return $newnames;
	}

	// Update a single release as it's created.
	public function matchPre($cleanerName, $releaseID)
	{
		$db = new DB();
		if($db->query(sprintf("UPDATE predb SET releaseID = %d WHERE name = %s AND releaseID = NULL", $releaseID, $db->escapeString($cleanerName))))
			$db->query(sprintf("UPDATE releases SET relnamestatus = 6 WHERE ID = %d", $releaseID));
	}

	// When a searchname is the same as the title, tie it to the predb. Try to update the categoryID at the same time.
	public function matchPredb()
	{
		$db = new DB();
		$updated = 0;
		if($this->echooutput)
			echo "Matching up predb titles with release search names.\n";

		if($res = $db->queryDirect("SELECT p.ID, p.category, r.ID AS releaseID FROM predb p inner join releases r ON p.title = r.searchname WHERE p.releaseID IS NULL"))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$db->query(sprintf("UPDATE predb SET releaseID = %d WHERE ID = %d", $row["releaseID"], $row["ID"]));
				$catName=str_replace(array("TV-", "TV: "), '', $row["category"]);
				if($catID = $db->queryOneRow(sprintf("SELECT ID FROM category WHERE title = %s", $db->escapeString($catName))))
					$db->query(sprintf("UPDATE releases SET categoryID = %d WHERE ID = %d", $db->escapeString($catID["ID"]), $db->escapeString($row["releaseID"])));
				$db->query(sprintf("UPDATE releases SET relnamestatus = 6 WHERE ID = %d", $row["releaseID"]));
				if($this->echooutput)
					echo ".";
				$updated++;
			}
		}
		return $updated;
	}

	// Look if the release is missing an nfo.
	public function matchNfo()
	{
		$db = new DB();
		$nfos = 0;
		if($this->echooutput)
			echo "Matching up predb NFOs with releases missing an NFO.\n";

		if($res = $db->queryDirect("SELECT r.ID, p.nfo, r.completion, r.guid, r.groupID FROM releases r inner join predb p ON r.ID = p.releaseID WHERE p.nfo IS NOT NULL AND r.nfostatus != 1 LIMIT 100"))
		{
			$nfo = new Nfo($this->echooutput);
			$nzbcontents = new Nzbcontents($this->echooutput);
			while ($row = mysqli_fetch_assoc($res))
			{
				$buffer = getUrl($row["nfo"]);
				if ($buffer !== false && strlen($buffer))
				{
					if($nfo->addAlternateNfo($db, $buffer, $row))
					{
						if($this->echooutput)
							echo ".";
						$nfos++;
					}
				}
			}
			return $nfos;
		}
	}

	// Matches the MD5 within the predb table to release files and subjects (names) which are hashed.
	public function parseTitles($time, $echo, $cats, $namestatus, $md5="")
	{
		$db = new DB();
		$namefixer = new Namefixer();
		$updated = 0;

		$tq = "";
		if ($time == 1)
			$tq = " and r.adddate > (now() - interval 3 hour)";
		$ct = "";
		if ($cats == 1)
			$ct = " and r.categoryID in (1090, 2020, 3050, 6050, 5050, 7010, 8050)";

		if($this->echooutput)
		{
			$te = "";
			if ($time == 1)
				$te = " in the past 3 hours";
			echo "Fixing search names".$te." using the predb md5.\n";
		}
		if ($res = $db->queryDirect("select r.ID, r.name, r.searchname, r.categoryID, r.groupID, rf.name as filename from releases r left join releasefiles rf on r.ID = rf.releaseID  where (r.name REGEXP'[a-fA-F0-9]{32}' or rf.name REGEXP'[a-fA-F0-9]{32}') and r.relnamestatus = 1 and r.categoryID = 7010 and passwordstatus >= -1 ORDER BY rf.releaseID, rf.size DESC ".$tq))
		{
			while($row = mysqli_fetch_assoc($res))
			{
				if (preg_match("/[a-f0-9]{32}/i", $row["name"], $matches))
					$updated = $updated + $namefixer->matchPredbMD5($matches[0], $row, $echo, $namestatus, $this->echooutput);
				else if (preg_match("/[a-f0-9]{32}/i", $row["filename"], $matches))
					$updated = $updated + $namefixer->matchPredbMD5($matches[0], $row, $echo, $namestatus, $this->echooutput);
			}
		}
		return $updated;
	}

	public function getAll($offset, $offset2)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT p.*, r.guid FROM predb p left join releases r on p.releaseID = r.ID ORDER BY p.adddate DESC limit %d,%d", $offset, $offset2));
	}

	public function getCount()
	{
		$db = new DB();
		$count = $db->queryOneRow("SELECT count(*) as cnt from predb");
		return $count["cnt"];
	}
}
