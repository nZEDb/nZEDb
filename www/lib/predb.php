<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nfo.php");
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
		if (strtotime($newestrel["adddate"]) < time()-600)
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
			echo "Matched ".$matched." predDB titles to release search names.\n";
		$nfos = $this->matchNfo();
		if ($nfos > 0 && $this->echooutput)
			echo "Added ".$nfos." missing NFOs from preDB sources.\n";
		return $newnames;
	}

	public function retrieveWomble()
	{
		$db = new DB();
		$newnames = 0;

		$date = new DateTime();
		$today = date('Ymd')."\n";
		$strDateFrom='20130621';
		$arr = $this->dateRangeArray($strDateFrom,$today);
		foreach ($arr as $day) {
			$buffer = getUrl("http://www.newshost.co.za/?date=".$day);
			echo "http://www.newshost.co.za/?date".$day."\n";
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
										if (!isset($matches2["size1"]) && empty($matches["size1"]))
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
									if (!isset($matches2["size1"]) && empty($matches["size1"]))
										$size = "NULL";
									else
										$size = $db->escapeString($matches2["size1"].$matches2["size2"]);

									if ($matches2["nfo"] == "")
										$nfo = "NULL";
									else
										$nfo = $db->escapeString("http://nzb.isasecret.com/".$matches2["nfo"]);

									$db->query(sprintf("UPDATE predb SET nfo = %s, size = %s, category = %s, predate = FROM_UNIXTIME(".strtotime($matches2["date"])."), adddate = now(), source = %s where ID = %d", $nfo, $size, $db->escapeString($matches2["category"]), $db->escapeString("womble"), $oldname["ID"]));
									//$db->query(sprintf("INSERT IGNORE INTO predb (title, nfo, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $nfo, $size, $db->escapeString($matches2["category"]), $db->escapeString("womble"), $db->escapeString(md5($matches2["title"]))));
									$newnames++;
								}
							}
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
		echo "http://rss.omgwtfnzbs.org/rss-info.php\n";
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

								$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->escapeString("omgwtfnzbs"), $db->escapeString(md5($matches2["title"]))));
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
		echo "http://pre.zenet.org/live.php\n";
		if ($buffer !== false && strlen($buffer))
		{
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

								$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $category, $db->escapeString("zenet"), $db->escapeString(md5($matches2["title"]))));
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

		foreach (range(1, 500, 50) as $number) {
			$buffer = getUrl("http://www.prelist.ws/?start=".$number);
			echo "http://www.prelist.ws/?start=".$number."\n";
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
									if (!isset($matches2["size"]) && empty($matches["size"]))
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
		}
		return $newnames;
	}

	public function retrieveOrlydb()
	{
		$db = new DB();
		$newnames = 0;

		foreach (range(1, 10) as $number) {
			$buffer = getUrl("http://www.orlydb.com/".$number);
			echo "http://www.orlydb.com/".$number."\n";
			if ($buffer !== false && strlen($buffer))
			{
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

										$db->query(sprintf("INSERT IGNORE INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, FROM_UNIXTIME(".strtotime($matches2["date"])."), now(), %s, %s)", $db->escapeString($matches2["title"]), $size, $db->escapeString($matches2["category"]), $db->escapeString("orlydb"), $db->escapeString(md5($matches2["title"]))));
										$newnames++;
									}
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
		echo "http://www.srrdb.com/feed/srrs\n";
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
		$releases = @simplexml_load_file('http://predb.me/?rss');
		echo "http://predb.me/?rss\n";
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
		return $newnames;
	}

	// When a searchname is the same as the title, tie it to the predb.
	public function matchPredb()
	{
		$db = new DB();
		$updated = 0;
		if($this->echooutput)
			echo "Matching up predb titles with release search names.\n";

		if($res = $db->queryDirect("SELECT p.ID, p.category, r.ID as releaseID from predb p inner join releases r on p.title = r.searchname where p.releaseID is null"))
//		if($res = $db->queryDirect("SELECT p.ID, p.category, r.ID as releaseID from predb p inner join releases r on p.title = r.searchname"))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$db->query(sprintf("UPDATE predb SET releaseID = %d where ID = %d", $row["releaseID"], $row["ID"]));
				$catName=str_replace("TV-", '', $row["category"]);
				$catName=str_replace("TV: ", '', $catName);
				if($catID = $db->queryOneRow(sprintf("select ID from category where title = %s", $db->escapeString($catName))))
				{
					//print($row["category"]." - ".$catID["ID"]."\n");
					$db->query(sprintf("UPDATE releases set categoryID = %d where ID = %d", $db->escapeString($catID["ID"]), $db->escapeString($row["ID"])));
				}
				echo ".";
				$updated++;
			}
			return $updated;
		}
		if($res = $db->queryDirect("SELECT p.ID, r.ID as releaseID from predb p inner join releases r on p.title = r.name where p.releaseID is null"))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$db->query(sprintf("UPDATE predb SET releaseID = %d where ID = %d", $row["releaseID"], $row["ID"]));
				echo ".";
				$updated++;
			}
			return $updated;
		}

	}

	// Look if the release is missing an nfo.
	public function matchNfo()
	{
		$db = new DB();
		$nfos = 0;
		if($this->echooutput)
			echo "Matching up predb NFOs with releases missing an NFO.\n";

		if($res = $db->queryDirect("SELECT r.ID, p.nfo from releases r inner join predb p on r.ID = p.releaseID where p.nfo is not null and r.nfostatus != 1"))
		//if($res = $db->queryDirect("SELECT r.ID, p.nfo from releases r inner join predb p on r.ID = p.releaseID where p.nfo is not null"))
		{
			$nfo = new Nfo($this->echooutput);
			while ($row = mysqli_fetch_assoc($res))
			{
				$buffer = getUrl($row["nfo"]);
				if ($buffer !== false && strlen($buffer))
				{
					$nfo->addReleaseNfo($row["ID"]);
					$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($buffer), $row["ID"]));
					$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $row["ID"]));
					echo ".";
					$nfos++;
				}
			}
			return $nfos;
		}
	}

	// Matches the names within the predb table to release files and subjects (names). In the future, use the MD5.
	public function parseTitles($time, $echo, $cats, $namestatus, $md5="")
	{
		$db = new DB();
		$updated = 0;

		/*if($backfill = "" && $this->echooutput)
		{
			$te = "";
			if ($time == 1)
				$te = " in the past 3 hours";
			echo "Fixing search names".$te." using the predb titles.\n";
		}*/

		$tq = "";
		if ($time == 1)
			$tq = " and r.adddate > (now() - interval 3 hour)";
		$ct = "";
		if ($cats == 1)
			$ct = " and r.categoryID in (1090, 2020, 3050, 6050, 5050, 7010, 8050)";

		/*if($backfill = "" && $res = $db->queryDirect("SELECT r.searchname, r.categoryID, r.groupID, p.source, p.title, r.ID from releases r left join releasefiles rf on rf.releaseID = r.ID, predb p where (r.name like concat('%', p.title, '%') or rf.name like concat('%', p.title, '%')) and r.relnamestatus = 1".$tq.$ct))
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
							$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 3 where ID = %d", $db->escapeString($row["title"]), $determinedcat, $row["ID"]));
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
							"Method:   "."predb titles: ".$row["source"]."\n"."\n";
					}
					$updated++;
				}
			}
		}*/
		if($this->echooutput)
		{
			$te = "";
			if ($time == 1)
				$te = " in the past 3 hours";
			echo "Fixing search names".$te." using the predb md5.\n";
		}
		if ($res = $db->queryDirect("select r.ID, r.name, r.searchname, r.categoryID, r.groupID, rf.name as filename from releases r left join releasefiles rf on r.ID = rf.releaseID  where (r.name REGEXP'[a-fA-F0-9]{32}' or rf.name REGEXP'[a-fA-F0-9]{32}') and r.relnamestatus = 1 and r.categoryID = 7010 and passwordstatus >= 0 ORDER BY rf.releaseID, rf.size DESC ".$tq))
		{
			while($row = mysqli_fetch_assoc($res))
			{
				if (preg_match("/[a-f0-9]{32}/i", $row["name"], $matches))
				{
					$a = $db->query("select title, source from predb where md5 = '".$matches[0]."'");
					foreach ($a as $b)
					{
						if ($b["title"] !== $row["searchname"])
						{
							$category = new Category();
							$determinedcat = $category->determineCategory($b["title"], $row["groupID"]);

							if ($echo == 1)
							{
								if ($namestatus == 1)
									$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 3 where ID = %d", $db->escapeString($b["title"]), $determinedcat, $row["ID"]));
								else
									$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d where ID = %d", $db->escapeString($b["title"]), $determinedcat, $row["ID"]));
							}
							if ($this->echooutput)
							{
								$groups = new Groups();

								echo"New name: ".$b["title"]."\n".
									"Old name: ".$row["searchname"]."\n".
									"New cat:  ".$category->getNameByID($determinedcat)."\n".
									"Old cat:  ".$category->getNameByID($row["categoryID"])."\n".
									"Group:    ".$groups->getByNameByID($row["groupID"])."\n".
									"Method:   "."predb md5 release name: ".$b["source"]."\n"."\n";
							}
							$updated++;
						}
					}
				}
				else if (preg_match("/[a-f0-9]{32}/i", $row["filename"], $matches))
				{
					$a = $db->query("select title, source from predb where md5 = '".$matches[0]."'");
					foreach ($a as $b)
					{
						if ($b["title"] !== $row["searchname"])
						{
							$category = new Category();
							$determinedcat = $category->determineCategory($b["title"], $row["groupID"]);

							if ($echo == 1)
							{
								if ($namestatus == 1)
									$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 3 where ID = %d", $db->escapeString($b["title"]), $determinedcat, $row["ID"]));
								else
									$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d where ID = %d", $db->escapeString($b["title"]), $determinedcat, $row["ID"]));
							}
							if ($this->echooutput)
							{
								$groups = new Groups();

								echo"New name: ".$b["title"]."\n".
									"Old name: ".$row["searchname"]."\n".
									"New cat:  ".$category->getNameByID($determinedcat)."\n".
									"Old cat:  ".$category->getNameByID($row["categoryID"])."\n".
									"Group:    ".$groups->getByNameByID($row["groupID"])."\n".
									"Method:   "."predb md5 file name: ".$b["source"]."\n"."\n";
							}
							$updated++;
						}
					}
				}
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

	function dateRangeArray($start, $end)
	{
		$range = array();

		if (is_string($start) === true) $start = strtotime($start);
		if (is_string($end) === true ) $end = strtotime($end);

		do {
			$range[] = date('Ymd', $start);
			$start = strtotime("+ 1 day", $start);
		} while($start <= $end);

		return $range;
	}
}
