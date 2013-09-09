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
		$newestrel = $db->queryOneRow("SELECT adddate, id FROM predb ORDER BY adddate DESC LIMIT 1");
		if (strtotime($newestrel['adddate']) < time()-600 || is_null($newestrel['adddate']))
		{
			if ($this->echooutput)
				echo "Retrieving titles from preDB sources.\n";
			$newwomble = $this->retrieveWomble();
			if ($this->echooutput)
				echo $newwomble." Retrieved.\n";
			$newomgwtf = $this->retrieveOmgwtfnzbs();
			if ($this->echooutput)
				echo $newomgwtf." Retrieved.\n";
			$newzenet = $this->retrieveZenet();
			if ($this->echooutput)
				echo $newzenet." Retrieved.\n";
			$newprelist = $this->retrievePrelist();
			if ($this->echooutput)
				echo $newprelist." Retrieved.\n";
			$neworly = $this->retrieveOrlydb();
			if ($this->echooutput)
				echo $neworly." Retrieved.\n";
			$newsrr = $this->retrieveSrr();
			if ($this->echooutput)
				echo $newsrr." Retrieved.\n";
			$newpdme = $this->retrievePredbme();
			if ($this->echooutput)
				echo $newpdme." Retrieved.\n";
			$newnames = $newwomble+$newomgwtf+$newzenet+$newprelist+$neworly+$newsrr+$newpdme;
			if(count($newnames) > 0)
				$db->queryExec(sprintf("UPDATE predb SET adddate = NOW() WHERE id = %d", $newestrel["id"]));
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
		$newnames = $updated = 0;

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
							$md5 = md5($matches2["title"]);
							$oldname = $db->queryOneRow(sprintf("SELECT md5, source, id, nfo FROM predb WHERE md5 = %s", $db->escapeString($md5)));
							if ($oldname !== false && $oldname["md5"] == $md5)
							{
								if ($oldname["nfo"] != NULL)
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

									$db->queryExec(sprintf("UPDATE predb SET nfo = %s, size = %s, category = %s, predate = %s, adddate = now(), source = %s where id = %d", $nfo, $size, $db->escapeString($matches2["category"]), $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("womble"), $oldname["id"]));
									$updated++;
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

								$db->queryExec(sprintf("INSERT INTO predb (title, nfo, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, %s, now(), %s, %s)", $db->escapeString($matches2["title"]), $nfo, $size, $db->escapeString($matches2["category"]), $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("womble"), $db->escapeString($md5)));
								$newnames++;
							}
						}
					}
				}
			}
		}
		echo $updated." Updated.\n";
		return $newnames;
	}

	public function retrieveOmgwtfnzbs()
	{
		$db = new DB();
		$newnames = $updated = 0;

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
							$md5 = md5($matches2["title"]);
							$oldname = $db->queryOneRow(sprintf("SELECT md5, source, id FROM predb WHERE md5 = %s", $db->escapeString($md5)));
							if ($oldname !== false && $oldname["md5"] == $md5)
							{
								if ($oldname["source"] == "womble" || $oldname["source"] == "omgwtfnzbs")
									continue;
								else
								{
									$size = $db->escapeString(round($matches2["size1"]).$matches2["size2"]);
									$db->queryExec(sprintf("UPDATE predb SET size = %s, category = %s, predate = %s, adddate = now(), source = %s where id = %d", $size, $db->escapeString($matches2["category"]), $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("omgwtfnzbs"), $oldname["id"]));
									$updated++;
								}
							}
							else
							{
								$size = $db->escapeString(round($matches2["size1"]).$matches2["size2"]);
								$title = preg_replace("/  - omgwtfnzbs.org/", "", $matches2["title"]);
								$db->queryExec(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)", $db->escapeString($title), $size, $db->escapeString($matches2["category"]), $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("omgwtfnzbs"), $db->escapeString($md5)));
								$newnames++;
							}
						}
					}
				}
			}
		}
		echo $updated." Updated.\n";
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
							$md5 = md5($matches2["title"].$matches2["title2"]);
							$oldname = $db->queryOneRow(sprintf("SELECT md5 FROM predb WHERE md5 = %s", $db->escapeString($md5)));
							if ($oldname !== false && $oldname["md5"] == $md5)
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

								$db->queryExec(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)", $db->escapeString($matches2["title"].$matches2["title2"]), $size, $category, $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("zenet"), $db->escapeString($md5)));
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
							$md5 = md5($matches2["title"]);
							$oldname = $db->queryOneRow(sprintf("SELECT md5 FROM predb WHERE md5 = %s", $db->escapeString($md5)));
							if ($oldname !== false && $oldname["md5"] == $md5)
								continue;
							else
							{
								if (!isset($matches2["size"]) && empty($matches2["size"]))
									$size = "NULL";
								else
									$size = $db->escapeString(round($matches2["size"]));

								$db->queryExec(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("prelist"), $db->escapeString($md5)));
								$newnames++;
							}
						}
						else if (preg_match('/">\[ (?P<date>.+?) U.+?">(?P<category>.+?)<\/a>.+?">(?P<category1>.+?)<\/a.+">(?P<title>.+?)<\/a>/si', $m, $matches2))
						{
							$md5 = md5($matches2["title"]);
							$oldname = $db->queryOneRow(sprintf("SELECT md5 FROM predb WHERE md5 = %s", $db->escapeString($md5)));
							if ($oldname !== false && $oldname["md5"] == $md5)
								continue;
							else
							{
								$category = $db->escapeString($matches2["category"].", ".$matches2["category1"]);

								$db->queryExec(sprintf("INSERT INTO predb (title, category, predate, adddate, source, md5) VALUES (%s, %s, %s, now(), %s, %s)", $db->escapeString($matches2["title"]), $category, $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("prelist"), $db->escapeString($md5)));
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
								$md5 = md5($matches2["title"]);
								$oldname = $db->queryOneRow(sprintf("SELECT md5 FROM predb WHERE md5 = %s", $db->escapeString($md5)));
								if ($oldname !== false && $oldname["md5"] == $md5)
									continue;
								else
								{
									if (!isset($matches2["size"]) && empty($matches2["size"]))
										$size = "NULL";
									else
										$size = $db->escapeString($matches2["size"]);

									$db->queryExec(sprintf("INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->from_unixtime(strtotime($matches2["date"])), $db->escapeString("orlydb"), $db->escapeString($md5)));
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
				$md5 = md5($release->title);
				$oldname = $db->queryOneRow(sprintf("SELECT md5 FROM predb WHERE md5 = %s", $db->escapeString($md5)));
				if ($oldname !== false && $oldname["md5"] == $md5)
					continue;
				else
				{
					$db->queryExec(sprintf("INSERT INTO predb (title, predate, adddate, source, md5) VALUES (%s, %s, now(), %s, %s)", $db->escapeString($release->title), $db->from_unixtime($release->pubDate), $db->escapeString("srrdb"), $db->escapeString($md5)));
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
					$md5 = md5($release->title);
					$oldname = $db->queryOneRow(sprintf("SELECT md5 FROM predb WHERE md5 = %s", $db->escapeString($md5)));
					if ($oldname !== false && $oldname["md5"] == $md5)
						continue;
					else
					{
						$db->queryExec(sprintf("INSERT INTO predb (title, predate, adddate, source, md5) VALUES (%s, now(), now(), %s, %s)", $db->escapeString($release->title), $db->escapeString("predbme"), $db->escapeString($md5)));
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
		if($x = $db->queryOneRow(sprintf("SELECT id FROM predb WHERE title = %s", $db->escapeString($cleanerName))) !== false)
		{
			$db->queryExec(sprintf("UPDATE releases SET relnamestatus = 11, preid = %d WHERE id = %d", $x["id"], $releaseID));
		}
	}

	// When a searchname is the same as the title, tie it to the predb. Try to update the categoryID at the same time.
	public function matchPredb()
	{
		/*
		 * For future reference, mysql 5.6 innodb has fulltext searching support.
		 * INSERT INTO releases (name) VALUES ('[149787]-[FULL]-[#a.b.teevee]-[ The.Amazing.World.of.Gumball.S01E28.The.Club.720p.HDTV.x264-W4F ]-[1/1] - "The.Amazing.World.of.Gumball.S01E28.The.Club.720p.HDTV.x264-W4F.nzb" yEnc');
		 * ALTER TABLE releases ADD FULLTEXT(name);
		 * SELECT * FROM releases WHERE MATCH (name) AGAINST ('"The.Amazing.World.of.Gumball.S01E28.The.Club.720p.HDTV.x264-W4F"' IN BOOLEAN MODE);
		 *
		 * In myisam this is much faster than SELECT * FROM releases WHERE name LIKE '%The.Amazing.World.of.Gumball.S01E28.The.Club.720p.HDTV.x264-W4F%';
		 * So I'm guessing in innodb it will be the same.
		 */
		$db = new DB();
		$updated = 0;
		if($this->echooutput)
			echo "Matching up predb titles with release search names.\n";

		$res = $db->query("SELECT p.id AS preid, r.id AS releaseid FROM predb p INNER JOIN releases r ON p.title = r.searchname WHERE r.preid IS NULL");
		if(count($res) > 0)
		{
			foreach ($res as $row)
			{
				$db->queryExec(sprintf("UPDATE releases SET preid = %d, relnamestatus = 11 WHERE id = %d", $row["preid"], $row["releaseid"]));
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

		$res = $db->query("SELECT r.id, p.nfo, r.completion, r.guid, r.groupid FROM releases r INNER JOIN predb p ON r.preid = p.id WHERE p.nfo IS NOT NULL AND r.nfostatus != 1 LIMIT 100");
		if(count($res) > 0)
		{
			$nfo = new Nfo($this->echooutput);
			$nzbcontents = new Nzbcontents($this->echooutput);
			foreach ($res as $row)
			{
				$buffer = getUrl($row["nfo"]);
				if ($buffer !== false)
				{
					if($nfo->addAlternateNfo($db, $buffer, $row))
					{
						if($this->echooutput)
							echo "+";
						$nfos++;
					}
					echo "-";
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
			$tq = " AND r.adddate > (NOW() - INTERVAL 3 HOUR)";
		$ct = "";
		if ($cats == 1)
			$ct = " AND r.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050)";

		if($this->echooutput)
		{
			$te = "";
			if ($time == 1)
				$te = " in the past 3 hours";
			echo "Fixing search names".$te." using the predb md5.\n";
		}
		if ($db->dbSystem() == "mysql")
			$regex = "(r.name REGEXP'[a-fA-F0-9]{32}' OR rf.name REGEXP'[a-fA-F0-9]{32}')";
		else if ($db->dbSystem() == "pgsql")
			$regex = "(regexp_matches(r.name, '[a-fA-F0-9]{32}') OR regexp_matches(rf.name, '[a-fA-F0-9]{32}'))";

		$res = $db->query(sprintf("SELECT DISTINCT r.id, r.name, r.searchname, r.categoryid, r.groupid, rf.name AS filename FROM releases r LEFT JOIN releasefiles rf ON r.id = rf.releaseid WHERE {$regex} AND r.relnamestatus IN (0, 1, 20) AND dehashstatus BETWEEN -5 AND 0 AND r.categoryid = 7010 AND passwordstatus >= -1 %s ORDER BY rf.releaseid, rf.size DESC ", $tq));
		if (count($res) > 0)
		{
			foreach ($res as $row)
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
		return $db->query(sprintf("SELECT p.*, r.guid FROM predb p LEFT OUTER JOIN releases r ON p.id = r.preid ORDER BY p.adddate DESC LIMIT %d OFFSET %d", $offset2, $offset));
	}

	public function getCount()
	{
		$db = new DB();
		$count = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM predb");
		return $count["cnt"];
	}

	// Returns a single row for a release.
	public function getForRelease($preID)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM predb WHERE id = %d", $preID));
	}

	public function getOne($preID)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM predb WHERE id = %d", $preID));
	}
}
