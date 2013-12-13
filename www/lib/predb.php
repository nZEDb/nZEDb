<?php
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'category.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'nfo.php';
require_once nZEDb_LIB . 'namefixer.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'consoletools.php';
require_once nZEDb_LIB . 'ColorCLI.php';

/*
 * Class for inserting names/categories/md5 etc from predb sources into the DB, also for matching names on files / subjects.
 */

Class Predb
{
	function __construct($echooutput=false)
	{
		$s = new Sites();
		$this->site = $s->get();
		$this->echooutput = $echooutput;
		$this->db = new DB();
		$this->c = new ColorCLI;
	}

	// Retrieve pre info from predb sources and store them in the DB.
	// Returns the quantity of new titles retrieved.
	public function updatePre()
	{
		$db = $this->db;
		$newnames = 0;
		$newestrel = $db->queryOneRow('SELECT adddate, id FROM predb ORDER BY adddate DESC LIMIT 1');
		if (strtotime($newestrel['adddate']) < time()-600 || is_null($newestrel['adddate']))
		{
			if ($this->echooutput)
				echo "Retrieving titles from preDB sources.\n";
			$newwomble = $this->retrieveWomble();
			if ($this->echooutput)
				echo $newwomble." \tRetrieved from Womble.\n";
			$newomgwtf = $this->retrieveOmgwtfnzbs();
			if ($this->echooutput)
				echo $newomgwtf." \tRetrieved from Omgwtfnzbs.\n";
			$newzenet = $this->retrieveZenet();
			if ($this->echooutput)
				echo $newzenet." \tRetrieved from Zenet.\n";
			$newprelist = $this->retrievePrelist();
			if ($this->echooutput)
				echo $newprelist." \tRetrieved from Prelist.\n";
			$neworly = $this->retrieveOrlydb();
			if ($this->echooutput)
				echo $neworly." \tRetrieved from Orlydb.\n";
			$newsrr = $this->retrieveSrr();
			if ($this->echooutput)
				echo $newsrr." \tRetrieved from Srrdb.\n";
			$newpdme = $this->retrievePredbme();
			if ($this->echooutput)
				echo $newpdme." \tRetrieved from Predbme.\n";
			$this->retrieveAllfilledMoovee();
			$this->retrieveAllfilledTeevee();
			$this->retrieveAllfilledErotica();
			$this->retrieveAllfilledForeign();
			$newnames = $newwomble+$newomgwtf+$newzenet+$newprelist+$neworly+$newsrr+$newpdme;
			if(count($newnames) > 0)
				$db->queryExec(sprintf('UPDATE predb SET adddate = NOW() WHERE id = %d', $newestrel['id']));
			return $newnames;
		}
	}

	// Attempts to match predb to releases.
	public function checkPre($nntp)
	{
		$matched = $this->matchPredb();
		if ($matched > 0 && $this->echooutput)
			echo 'Matched '.$matched." predDB titles to release search names.\n";
		$nfos = $this->matchNfo($nntp);
		if ($nfos > 0 && $this->echooutput)
			echo "\nAdded ".$nfos." missing NFOs from preDB sources.\n";
	}

	public function retrieveWomble()
	{
		$db = new DB();
		$newnames = $updated = 0;

		$buffer = getUrl('http://www.newshost.co.za');
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
							$md5 = md5($matches2['title']);
							$oldname = $db->queryOneRow(sprintf('SELECT md5, source, id, nfo FROM predb WHERE md5 = %s', $db->escapeString($md5)));
							if ($oldname !== false && $oldname['md5'] == $md5)
							{
								if ($oldname['nfo'] != NULL)
									continue;
								else
								{
									if (!isset($matches2['size1']) && empty($matches2['size1']))
										$size = 'NULL';
									else
										$size = $db->escapeString($matches2['size1'].$matches2['size2']);

									if ($matches2['nfo'] == '')
										$nfo = 'NULL';
									else
										$nfo = $db->escapeString('http://nzb.isasecret.com/'.$matches2['nfo']);

									$db->queryExec(sprintf('UPDATE predb SET nfo = %s, size = %s, category = %s, predate = %s, adddate = now(), source = %s where id = %d', $nfo, $size, $db->escapeString($matches2['category']), $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('womble'), $oldname['id']));
									$updated++;
								}
							}
							else
							{
								if (!isset($matches2['size1']) && empty($matches2['size1']))
									$size = 'NULL';
								else
									$size = $db->escapeString($matches2['size1'].$matches2['size2']);

								if ($matches2['nfo'] == '')
									$nfo = 'NULL';
								else
									$nfo = $db->escapeString('http://nzb.isasecret.com/'.$matches2['nfo']);

								$db->queryExec(sprintf('INSERT INTO predb (title, nfo, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, %s, now(), %s, %s)', $db->escapeString($matches2['title']), $nfo, $size, $db->escapeString($matches2['category']), $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('womble'), $db->escapeString($md5)));
								$newnames++;
							}
						}
					}
				}
			}
		}
		echo $updated." \tUpdated from Womble.\n";
		return $newnames;
	}

	public function retrieveOmgwtfnzbs()
	{
		$db = new DB();
		$newnames = $updated = 0;

		$buffer = getUrl('http://rss.omgwtfnzbs.org/rss-info.php');
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
							$md5 = md5($matches2['title']);
							$oldname = $db->queryOneRow(sprintf('SELECT md5, source, id FROM predb WHERE md5 = %s', $db->escapeString($md5)));
							if ($oldname !== false && $oldname['md5'] == $md5)
							{
								if ($oldname['source'] == 'womble' || $oldname['source'] == 'omgwtfnzbs')
									continue;
								else
								{
									$size = $db->escapeString(round($matches2['size1']).$matches2['size2']);
									$db->queryExec(sprintf('UPDATE predb SET size = %s, category = %s, predate = %s, adddate = now(), source = %s where id = %d', $size, $db->escapeString($matches2['category']), $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('omgwtfnzbs'), $oldname['id']));
									$updated++;
								}
							}
							else
							{
								$size = $db->escapeString(round($matches2['size1']).$matches2['size2']);
								$title = preg_replace('/\s+- omgwtfnzbs\.org/', '', $matches2['title']);
								$db->queryExec(sprintf('INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)', $db->escapeString($title), $size, $db->escapeString($matches2['category']), $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('omgwtfnzbs'), $db->escapeString($md5)));
								$newnames++;
							}
						}
					}
				}
			}
		}
		echo $updated." \tUpdated from Omgwtfnzbs.\n";
		return $newnames;
	}

	public function retrieveZenet()
	{
		$db = new DB();
		$newnames = 0;

		$buffer = getUrl('http://pre.zenet.org/live.php');
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<div class="mini-layout fluid">((\s+\S+)?\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?<\/div>\s+<\/div>)/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<span class="bold">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2})<\/span>.+<a href="\?post=\d+"><b><font color="#\d+">(?P<title>.+)<\/font><\/b><\/a>.+<p><a href="\?cats=.+"><font color="#FF9900">(?P<category>.+)<\/font><\/a> \| (?P<size1>[\d\.,]+)?(?P<size2>[MGK]B)? \/.+<\/div>/s', $m, $matches2))
						{
							$predate = $db->escapeString($matches2['predate']);
							$md5 = $db->escapeString(md5($matches2['title']));
							$title = $db->escapeString($matches2['title']);
							$oldname = $db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $db->escapeString($md5)));
							if ($oldname !== false && $oldname['md5'] == $md5)
								continue;
							else
							{
								if (!isset($matches2['size1']) && empty($matches2['size1']))
									$size = 'NULL';
								else
									$size = $db->escapeString(round($matches2['size1']).$matches2['size2']);

								if (isset($matches2['category']) && !empty($matches2['category']))
									$category = $db->escapeString($matches2['category']);
								else
									$category = 'NULL';

								$run = $db->queryInsert(sprintf('INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)', $title, $size, $category, $predate, $db->escapeString('zenet'), $md5));
								if ($run)
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

		$buffer = getUrl('http://www.prelist.ws/');
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
							$md5 = md5($matches2['title']);
							$oldname = $db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $db->escapeString($md5)));
							if ($oldname !== false && $oldname['md5'] == $md5)
								continue;
							else
							{
								if (!isset($matches2['size']) && empty($matches2['size']))
									$size = 'NULL';
								else
									$size = $db->escapeString(round($matches2['size']));

								$db->queryExec(sprintf('INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)', $db->escapeString($matches2['title']), $size, $db->escapeString($matches2['category']), $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('prelist'), $db->escapeString($md5)));
								$newnames++;
							}
						}
						else if (preg_match('/">\[ (?P<date>.+?) U.+?">(?P<category>.+?)<\/a>.+?">(?P<category1>.+?)<\/a.+">(?P<title>.+?)<\/a>/si', $m, $matches2))
						{
							$md5 = md5($matches2['title']);
							$oldname = $db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $db->escapeString($md5)));
							if ($oldname !== false && $oldname['md5'] == $md5)
								continue;
							else
							{
								$category = $db->escapeString($matches2['category'].', '.$matches2['category1']);

								$db->queryExec(sprintf('INSERT INTO predb (title, category, predate, adddate, source, md5) VALUES (%s, %s, %s, now(), %s, %s)', $db->escapeString($matches2['title']), $category, $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('prelist'), $db->escapeString($md5)));
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

		$buffer = getUrl('http://www.orlydb.com/');
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
								$md5 = md5($matches2['title']);
								$oldname = $db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $db->escapeString($md5)));
								if ($oldname !== false && $oldname['md5'] == $md5)
									continue;
								else
								{
									if (!isset($matches2['size']) && empty($matches2['size']))
										$size = 'NULL';
									else
										$size = $db->escapeString($matches2['size']);

									$db->queryExec(sprintf('INSERT INTO predb (title, size, category, predate, adddate, source, md5) VALUES (%s, %s, %s, %s, now(), %s, %s)', $db->escapeString($matches2['title']), $size, $db->escapeString($matches2['category']), $db->from_unixtime(strtotime($matches2['date'])), $db->escapeString('orlydb'), $db->escapeString($md5)));
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
		$url = "http://www.srrdb.com/feed/srrs";

		$options = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>"Accept-language: en\r\n" .
					  "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
					  "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad 
		  )
		);

		$context = stream_context_create($options);
		$releases = file_get_contents($url, false, $context);
		$releases = @simplexml_load_string($releases);
		if ($releases !== false)
		{
			foreach ($releases->channel->item as $release)
			{
				$md5 = md5($release->title);
				$oldname = $db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $db->escapeString($md5)));
				if ($oldname !== false && $oldname['md5'] == $md5)
					continue;
				else
				{
					$db->queryExec(sprintf('INSERT INTO predb (title, predate, adddate, source, md5) VALUES (%s, %s, now(), %s, %s)', $db->escapeString($release->title), $db->from_unixtime(strtotime($release->pubDate)), $db->escapeString('srrdb'), $db->escapeString($md5)));$newnames++;
				}
			}
		}
		return $newnames;
	}

	public function retrievePredbme()
	{
		$db = new DB();
		$newnames = 0;
		$arr = array('http://predb.me/?cats=movies-sd&rss=1', 'http://predb.me/?cats=movies-hd&rss=1', 'http://predb.me/?cats=movies-discs&rss=1', 'http://predb.me/?cats=tv-sd&rss=1', 'http://predb.me/?cats=tv-hd&rss=1', 'http://predb.me/?cats=tv-discs&rss=1', 'http://predb.me/?cats=music-audio&rss=1', 'http://predb.me/?cats=music-video&rss=1', 'http://predb.me/?cats=music-discs&rss=1', 'http://predb.me/?cats=games-pc&rss=1', 'http://predb.me/?cats=games-xbox&rss=1', 'http://predb.me/?cats=games-playstation&rss=1', 'http://predb.me/?cats=games-nintendo&rss=1', 'http://predb.me/?cats=apps-windows&rss=1', 'http://predb.me/?cats=apps-linux&rss=1', 'http://predb.me/?cats=apps-mac&rss=1', 'http://predb.me/?cats=apps-mobile&rss=1', 'http://predb.me/?cats=books-ebooks&rss=1', 'http://predb.me/?cats=books-audio-books&rss=1', 'http://predb.me/?cats=xxx-videos&rss=1', 'http://predb.me/?cats=xxx-images&rss=1', 'http://predb.me/?cats=dox&rss=1', 'http://predb.me/?cats=unknown&rss=1');
		foreach ($arr as &$value)
		{
			$releases = @simplexml_load_file($value);
			if ($releases !== false)
			{
				foreach ($releases->channel->item as $release)
				{
					$md5 = md5($release->title);
					$oldname = $db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $db->escapeString($md5)));
					if ($oldname !== false && $oldname['md5'] == $md5)
						continue;
					else
					{
						$db->queryExec(sprintf('INSERT INTO predb (title, predate, adddate, source, md5) VALUES (%s, now(), now(), %s, %s)', $db->escapeString($release->title), $db->escapeString('predbme'), $db->escapeString($md5)));
						$newnames++;
					}
				}
			}
		}
		return $newnames;
	}

	public function retrieveAllfilledMoovee()
	{
		$db = new DB();
		$newnames = 0;
		$groups = new Groups();
		$groupid = $groups->getIDByName('alt.binaries.moovee');
		$buffer = @file_get_contents('http://abmoovee.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2))
						{
							if (isset($matches2["requestid"]) && isset($matches2["title"]))
							{
								$requestid = $matches2["requestid"];
								$title = $db->escapeString($matches2["title"]);
								$md5 = $db->escapeString(md5($matches2["title"]));
								$predate = $db->escapeString($matches2["predate"]);
								$source = $db->escapeString('allfilled');
								$run = $db->queryExec(sprintf("INSERT IGNORE INTO predb (title, predate, adddate, source, md5, requestid, groupid) VALUES (%s, %s, now(), %s, %s, %s, %d) ON DUPLICATE KEY UPDATE requestid = %d, groupid = %d", $title, $predate, $source, $md5, $requestid, $groupid, $requestid, $groupid));
							}
						}
					}
				}
			}
		}
	}

	public function retrieveAllfilledTeevee()
	{
		$db = new DB();
		$newnames = 0;
		$groups = new Groups();
		$groupid = $groups->getIDByName('alt.binaries.teevee');
		$buffer = @file_get_contents('http://abteevee.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2))
						{
							if (isset($matches2["requestid"]) && isset($matches2["title"]))
							{
								$requestid = $matches2["requestid"];
								$title = $db->escapeString($matches2["title"]);
								$md5 = $db->escapeString(md5($matches2["title"]));
								$predate = $db->escapeString($matches2["predate"]);
								$source = $db->escapeString('allfilled');
								$run = $db->queryExec(sprintf("INSERT IGNORE INTO predb (title, predate, adddate, source, md5, requestid, groupid) VALUES (%s, %s, now(), %s, %s, %s, %d) ON DUPLICATE KEY UPDATE requestid = %d, groupid = %d", $title, $predate, $source, $md5, $requestid, $groupid, $requestid, $groupid));
							}
						}
					}
				}
			}
		}
	}

	public function retrieveAllfilledErotica()
	{
		$db = new DB();
		$newnames = 0;
		$groups = new Groups();
		$groupid = $groups->getIDByName('alt.binaries.erotica');
		$buffer = @file_get_contents('http://aberotica.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2))
						{
							if (isset($matches2["requestid"]) && isset($matches2["title"]))
							{
								$requestid = $matches2["requestid"];
								$title = $db->escapeString($matches2["title"]);
								$md5 = $db->escapeString(md5($matches2["title"]));
								$predate = $db->escapeString($matches2["predate"]);
								$source = $db->escapeString('allfilled');
								$run = $db->queryExec(sprintf("INSERT IGNORE INTO predb (title, predate, adddate, source, md5, requestid, groupid) VALUES (%s, %s, now(), %s, %s, %s, %d) ON DUPLICATE KEY UPDATE requestid = %d, groupid = %d", $title, $predate, $source, $md5, $requestid, $groupid, $requestid, $groupid));
							}
						}
					}
				}
			}
		}
	}

	public function retrieveAllfilledForeign()
	{
		$db = new DB();
		$newnames = 0;
		$groups = new Groups();
		$groupid = $groups->getIDByName('alt.binaries.mom');
		$buffer = @file_get_contents('http://abforeign.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false && strlen($buffer))
		{
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches))
			{
				foreach ($matches as $match)
				{
					foreach ($match as $m)
					{
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?)<\/td>/s', $m, $matches2))
						{
							if (isset($matches2["requestid"]) && isset($matches2["title"]))
							{
								$requestid = $matches2["requestid"];
								$title = $db->escapeString($matches2["title"]);
								$md5 = $db->escapeString(md5($matches2["title"]));
								$predate = $db->escapeString($matches2["predate"]);
								$source = $db->escapeString('allfilled');
								$run = $db->queryExec(sprintf("INSERT IGNORE INTO predb (title, predate, adddate, source, md5, requestid, groupid) VALUES (%s, %s, now(), %s, %s, %s, %d) ON DUPLICATE KEY UPDATE requestid = %d, groupid = %d", $title, $predate, $source, $md5, $requestid, $groupid, $requestid, $groupid));
							}
						}
					}
				}
			}
		}
	}

	// Update a single release as it's created.
	public function matchPre($cleanerName, $releaseID)
	{
		$db = new DB();
		if($x = $db->queryOneRow(sprintf('SELECT id FROM predb WHERE title = %s', $db->escapeString($cleanerName))) !== false)
		{
			$db->queryExec(sprintf('UPDATE releases SET preid = %d WHERE id = %d', $x['id'], $releaseID));
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
		$consoletools = new ConsoleTools();
		$updated = 0;
		if($this->echooutput)
			echo $this->c->header('Querying DB for matches in preDB titles with release searchnames.');

		$res = $db->prepare('SELECT p.id AS preid, r.id AS releaseid FROM predb p INNER JOIN releases r ON p.title = r.searchname WHERE r.preid IS NULL');
		$res->execute();
		$total = $res->rowCount();
		if($total > 0)
		{
			echo "\n";
			foreach ($res as $row)
			{
				$run = $db->queryExec(sprintf('UPDATE releases SET preid = %d WHERE id = %d', $row['preid'], $row['releaseid']));
				if($this->echooutput)
					$consoletools->overWrite('Matching up preDB titles with release search names: '.$consoletools->percentString(++$updated,$total));
			}
			echo "\n";
		}
		return $updated;
	}

	// Look if the release is missing an nfo.
	public function matchNfo($nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(binaries->updateAllGroups).\n"));

		$db = new DB();
		$nfos = 0;
		if($this->echooutput)
			echo "\nMatching up predb NFOs with releases missing an NFO.\n";

		$res = $db->prepare('SELECT r.id, p.nfo, r.completion, r.guid, r.groupid FROM releases r INNER JOIN predb p ON r.preid = p.id WHERE p.nfo IS NOT NULL AND r.nfostatus != 1 LIMIT 100');
		$res->execute();
		$total = $res->rowCount();
		if($total > 0)
		{
			$nfo = new Nfo($this->echooutput);
			$nzbcontents = new Nzbcontents($this->echooutput);
			foreach ($res as $row)
			{
				$buffer = getUrl($row['nfo']);
				if ($buffer !== false)
				{
					if($nfo->addAlternateNfo($db, $buffer, $row, $nntp))
					{
						if($this->echooutput)
							echo '+';
						$nfos++;
					}
					else
					{
						if($this->echooutput)
							echo '-';
					}
				}
			}
			return $nfos;
		}
	}

	// Matches the MD5 within the predb table to release files and subjects (names) which are hashed.
	public function parseTitles($time, $echo, $cats, $namestatus, $md5='')
	{
		$db = new DB();
		$namefixer = new Namefixer();
		$updated = 0;

		$tq = '';
		if ($time == 1)
		{
			if ($db->dbSystem() == 'mysql')
				$tq = 'AND r.adddate > (NOW() - INTERVAL 3 HOUR)';
			else if ($db->dbSystem() == 'pgsql')
				$tq = "AND r.adddate > (NOW() - INTERVAL '3 HOURS')";
		}
		$ct = '';
		if ($cats == 1)
			$ct = 'AND r.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050)';

		if($this->echooutput)
		{
			$te = '';
			if ($time == 1)
				$te = ' in the past 3 hours';
			echo 'Fixing search names'.$te." using the predb md5.\n";
		}
		if ($db->dbSystem() == 'mysql')
			$regex = "AND ((r.bitwise & 512) = 512 OR rf.name REGEXP'[a-fA-F0-9]{32}')";
		else if ($db->dbSystem() == 'pgsql')
			$regex = "AND ((r.bitwise & 512) = 512 OR rf.name ~ '[a-fA-F0-9]{32}')";

		$res = $db->prepare(sprintf('SELECT DISTINCT r.id, r.name, r.searchname, r.categoryid, r.groupid, rf.name AS filename, rf.releaseid, rf.size FROM releases r LEFT JOIN releasefiles rf ON r.id = rf.releaseid WHERE (bitwise & 4) = 0 AND dehashstatus BETWEEN -5 AND 0 AND passwordstatus >= -1 %s %s %s ORDER BY rf.releaseid, rf.size DESC', $regex, $tq, $ct));
		$res->execute();
		if ($res->rowCount() > 0)
		{
			foreach ($res as $row)
			{
				if (preg_match('/[a-f0-9]{32}/i', $row['name'], $matches))
					$updated = $updated + $namefixer->matchPredbMD5($matches[0], $row, $echo, $namestatus, $this->echooutput);
				else if (preg_match('/[a-f0-9]{32}/i', $row['filename'], $matches))
					$updated = $updated + $namefixer->matchPredbMD5($matches[0], $row, $echo, $namestatus, $this->echooutput);
			}
		}
		return $updated;
	}

	public function getAll($offset, $offset2)
	{
		$db = new DB();
		if ($db->dbSystem() == 'mysql')
		{
			$parr = $db->query(sprintf('SELECT SQL_CALC_FOUND_ROWS p.*, r.guid FROM predb p LEFT OUTER JOIN releases r ON p.id = r.preid ORDER BY p.adddate DESC LIMIT %d OFFSET %d', $offset2, $offset));
			$pcount = $db->queryOneRow('SELECT FOUND_ROWS() AS t');
			return array('arr' => $parr, 'count' => $pcount['t']);
		}
		else
		{
			$parr = $db->query(sprintf('SELECT p.*, r.guid FROM predb p LEFT OUTER JOIN releases r ON p.id = r.preid ORDER BY p.adddate DESC LIMIT %d OFFSET %d', $offset2, $offset));
			return array('arr' => $parr, 'count' => $this->getCount());
		}
	}

	public function getCount()
	{
		$db = new DB();
		$count = $db->queryOneRow('SELECT COUNT(*) AS cnt FROM predb');
		return $count['cnt'];
	}

	// Returns a single row for a release.
	public function getForRelease($preID)
	{
		$db = new DB();
		return $db->query(sprintf('SELECT * FROM predb WHERE id = %d', $preID));
	}

	public function getOne($preID)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf('SELECT * FROM predb WHERE id = %d', $preID));
	}
}
