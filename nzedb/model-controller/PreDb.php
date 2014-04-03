<?php
require_once nZEDb_LIBS . "simple_html_dom.php";

use nzedb\utility;

/*
 * Class for inserting names/categories/md5 etc from PreDB sources into the DB, also for matching names on files / subjects.
 */
Class PreDb
{
	// If you wish to not get PRE from one of these sources, set it to false.
	const PRE_WOMBLE   = true;
	const PRE_OMGWTF   = true;
	const PRE_ZENET    = true;
	const PRE_PRELIST  = true;
	const PRE_ORLYDB   = true;
	const PRE_SRRDB    = true;
	const PRE_PREDBME  = true;
	const PRE_ABGXNET  = true;
	const PRE_UCRAWLER = true;
	const PRE_MOOVEE   = true;
	const PRE_TEEVEE   = true;
	const PRE_EROTICA  = true;
	const PRE_FOREIGN  = true;

	/**
	 * @var bool|stdClass
	 */
	protected $site;

	/**
	 * @var bool
	 */
	protected $echooutput;

	/**
	 * @var DB
	 */
	protected $db;

	/**
	 * @var ColorCLI
	 */
	protected $c;

	/**
	 * @param bool $echo
	 */
	public function __construct($echo = false)
	{
		$this->echooutput = ($echo && nZEDb_ECHOCLI);
		$this->db = new DB();
		$this->c = new ColorCLI();
	}

	/**
	 * Retrieve pre info from PreDB sources and store them in the DB.
	 *
	 * @return int The quantity of new titles retrieved.
	 */
	public function updatePre()
	{
		$newNames = 0;
		$newestRel = $this->db->queryOneRow("SELECT value AS adddate FROM site WHERE setting = 'lastpretime'");

		// Wait 10 minutes in between pulls.
		if ((int)$newestRel['adddate'] < (time() - 600)) {

			if ($this->echooutput) {
				echo $this->c->header("Retrieving titles from preDB sources.");
			}

			if (self::PRE_WOMBLE) {
				$newNames += $newWomble = $this->retrieveWomble();
				if ($this->echooutput) {
					echo $this->c->primary($newWomble . " \tRetrieved from Womble.");
				}
			}

			if (self::PRE_OMGWTF) {
				$newNames += $newOmgWtf = $this->retrieveOmgwtfnzbs();
				if ($this->echooutput) {
					echo $this->c->primary($newOmgWtf . " \tRetrieved from Omgwtfnzbs.");
				}
			}

			if (self::PRE_ZENET) {
				$newNames += $newZenet = $this->retrieveZenet();
				if ($this->echooutput) {
					echo $this->c->primary($newZenet . " \tRetrieved from Zenet.");
				}
			}

			if (self::PRE_PRELIST) {
				$newNames += $newPreList = $this->retrievePrelist();
				if ($this->echooutput) {
					echo $this->c->primary($newPreList . " \tRetrieved from Prelist.");
				}
			}

			if (self::PRE_ORLYDB) {
				$newNames += $newOrly = $this->retrieveOrlydb();
				if ($this->echooutput) {
					echo $this->c->primary($newOrly . " \tRetrieved from Orlydb.");
				}
			}

			if (self::PRE_SRRDB) {
				$newNames += $newSrr = $this->retrieveSrr();
				if ($this->echooutput) {
					echo $this->c->primary($newSrr . " \tRetrieved from Srrdb.");
				}
			}

			if (self::PRE_PREDBME) {
				$newNames += $newPdme = $this->retrievePredbme();
				if ($this->echooutput) {
					echo $this->c->primary($newPdme . " \tRetrieved from Predbme.");
				}
			}

			if (self::PRE_ABGXNET) {
				$newNames += $abgx = $this->retrieveAbgx();
				if ($this->echooutput) {
					echo $this->c->primary($abgx . " \tRetrieved from abgx.");
				}
			}

			if (self::PRE_UCRAWLER) {
				$newNames += $newUsenetCrawler = $this->retrieveUsenetCrawler();
				if ($this->echooutput) {
					echo $this->c->primary($newUsenetCrawler . " \tRetrieved from Usenet-Crawler.");
				}
			}

			if (self::PRE_MOOVEE) {
				$newNames += $newMoovee = $this->retrieveAllfilledMoovee();
				if ($this->echooutput) {
					echo $this->c->primary($newMoovee . " \tRetrieved from Allfilled Moovee.");
				}
			}

			if (self::PRE_TEEVEE) {
				$newNames += $newTeevee = $this->retrieveAllfilledTeevee();
				if ($this->echooutput) {
					echo $this->c->primary($newTeevee . " \tRetrieved from Allfilled Teevee.");
				}
			}

			if (self::PRE_EROTICA) {
				$newNames += $newErotica = $this->retrieveAllfilledErotica();
				if ($this->echooutput) {
					echo $this->c->primary($newErotica . " \tRetrieved from Allfilled Erotica.");
				}
			}

			if (self::PRE_FOREIGN) {
				$newNames += $newForeign = $this->retrieveAllfilledForeign();
				if ($this->echooutput) {
					echo $this->c->primary($newForeign . " \tRetrieved from Allfilled Foreign.\n");
				}
			}

			if ($this->echooutput) {
				echo $this->c->primary($newNames . " \tRetrieved from all the above sources..");
			}

			// If we found nothing, update the last added to now to reset the timer.
			$this->db->queryExec(sprintf("UPDATE site SET value = %s WHERE setting = 'lastpretime'", $this->db->escapeString(time())));
		}

		return $newNames;
	}

	/**
	 * Attempts to match PreDB titles / NFOs to releases.
	 *
	 * @param $nntp
	 */
	public function checkPre($nntp)
	{
		$matched = $this->matchPredb();
		if ($this->echooutput) {
			echo $this->c->header(
				'Matched ' . number_format(($matched > 0) ? $matched : 0) . ' predDB titles to release search names.'
			);
		}

		$nfos = $this->matchNfo($nntp);
		if ($this->echooutput) {
			echo $this->c->header(
				"\nAdded " . number_format(($nfos > 0) ? $nfos : 0) . ' missing NFOs from preDB sources.'
			);
		}
	}

	/**
	 * Retrieve new pre info from womble.
	 *
	 * @return int
	 */
	protected function retrieveWomble()
	{
		$newNames = $updated = 0;
		$buffer = $this->getUrl('http://www.newshost.co.za');
		if ($buffer !== false) {
			$matches = $match = $matches2 = array();
			if (preg_match_all('/<tr bgcolor=#[df]{6}>.+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<tr bgcolor=#[df]{6}>.+?<td>(?P<date>.+?)<\/td>(.+?right>(?P<size1>.+?)&nbsp;(?P<size2>.+?)<\/td.+?)?<td>(?P<category>.+?)<\/td.+?<a href=.+?(<a href="(?P<nfo>.+?)">nfo<\/a>.+)?<td>(?P<title>.+?)<\/td.+tr>/s', $m, $matches2)) {

							// If the title is too short, don't bother.
							if (strlen($matches2['title']) < 15) {
								continue;
							}

							$md5 = $this->db->escapeString(md5($matches2['title']));
							$oldName = $this->db->queryOneRow(sprintf('SELECT md5, source, id, nfo FROM predb WHERE md5 = %s', $md5));
							// If we have it already and have the NFO, continue.
							if ($oldName !== false && $oldName['nfo'] != NULL) {
								continue;
							}

							// Start forming data for the query.
							$nfo =
								($matches2['nfo'] == ''
									? 'NULL'
									: $this->db->escapeString('http://www.newshost.co.za/' . $matches2['nfo'])
								);
							$size =
								((!isset($matches['size1']) && empty($matches2['size1']))
									? 'NULL'
									: $this->db->escapeString($matches2['size1'] . $matches2['size2'])
								);
							$category = $this->db->escapeString($matches2['category']);
							$time = $this->db->from_unixtime(strtotime($matches2['date']));
							$source = $this->db->escapeString('womble');

							// If we already have it, update.
							if ($oldName !== false) {
								$this->db->queryExec(
									sprintf('
										UPDATE predb SET
											nfo = %s, size = %s, category = %s, predate = %s,
											source = %s
										WHERE id = %d',
										$nfo, $size, $category, $time, $source, $oldName['id']
									)
								);
								$updated++;
							} elseif ($this->db->queryExec(
								sprintf('
									INSERT INTO predb (title, nfo, size, category, predate, source, md5)
									VALUES (%s, %s, %s, %s, %s, %s, %s)',
									$this->db->escapeString($matches2['title']),
									$nfo, $size, $category, $time, $source, $md5))) {
								$newNames++;
							}
						}
					}
				}
			}
			if ($this->echooutput) {
				echo $this->c->primary($updated . " \tUpdated from Womble.");
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Womble failed.");
		}
		return $newNames;
	}

	/**
	 * Retrieve pre info from omg.
	 *
	 * @return int
	 */
	protected function retrieveOmgwtfnzbs()
	{
		$newNames = $updated = 0;
		$buffer = $this->getUrl('http://rss.omgwtfnzbs.org/rss-info.php');
		if ($buffer !== false) {
			$matches = $matches2 = $match = array();
			if (preg_match_all('/<item>.+?<\/item>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<title>(?P<title>.+?)\s+-\s+omgwtfnzbs\.org.*?<\/title.+?pubDate>(?P<date>.+?)<\/pubDate.+?gory:<\/b> (?P<category>.+?)<br \/.+?<\/b> (?P<size1>.+?) (?P<size2>[a-zA-Z]+)<b/s', $m, $matches2)) {

							// If the title is too short, don't bother.
							if (strlen($matches2['title']) < 15) {
								continue;
							}

							$title = $matches2['title'];
							$md5 = $this->db->escapeString(md5($title));
							$oldName = $this->db->queryOneRow(sprintf('SELECT md5, source, id FROM predb WHERE md5 = %s', $md5));

							// If we have it already and the source is womble or omg, continue.
							if ($oldName !== false && ($oldName['source'] === 'womble' || $oldName['source'] === 'omgwtfnzbs')) {
								continue;
							}

							$size = $this->db->escapeString(round($matches2['size1']) . $matches2['size2']);
							$category = $this->db->escapeString($matches2['category']);
							$time = $this->db->from_unixtime(strtotime($matches2['date']));
							$source = $this->db->escapeString('omgwtfnzbs');

							// If we have it already, update it.
							if ($oldName !== false) {
								$this->db->queryExec(
									sprintf('
										UPDATE predb
										SET size = %s, category = %s, predate = %s, source = %s
										WHERE id = %d',
										$size, $category, $time, $source, $oldName['id']
									)
								);
								$updated++;
							} elseif ($this->db->queryExec(
									sprintf('
										INSERT INTO predb (title, size, category, predate, source, md5)
										VALUES (%s, %s, %s, %s, %s, %s)',
										$this->db->escapeString($title), $size, $category, $time, $source, $md5))) {
								$newNames++;
							}
						}
					}
				}
			}
			if ($this ->echooutput) {
				echo $this->c->primary($updated . " \tUpdated from Omgwtfnzbs.");
			}
		} elseif ($this ->echooutput) {
			echo $this->c->error("Update from Omgwtfnzbs failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre data from zenet.
	 *
	 * @return int
	 */
	protected function retrieveZenet()
	{
		$newNames = 0;

		$buffer = $this->getUrl('http://pre.zenet.org/live.php');
		if ($buffer !== false) {
			$matches = $matches2 = $match = array();
			if (preg_match_all('/<div class="mini-layout fluid">((\s+\S+)?\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?(\S+\s+)?<\/div>\s+<\/div>)/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<span class="bold">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2})<\/span>.+<a href="\?post=\d+"><b><font color="#\d+">(?P<title>.+)<\/font><\/b><\/a>.+<p><a href="\?cats=.+"><font color="#FF9900">(?P<category>.+)<\/font><\/a> \| (?P<size1>[\d\.,]+)?(?P<size2>[MGK]B)? \/\s+(?P<files>\d+).+<\/div>/s', $m, $matches2)) {

							// If it's too short, don't bother.
							if (strlen($matches2['title']) < 15) {
								continue;
							}

							$md5 = $this->db->escapeString(md5($matches2['title']));
							$dupeCheck = $this->db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $md5));

							// If we already have it, skip.
							if ($dupeCheck !== false) {

								continue;
							} elseif ($this->db->queryExec(
								sprintf('
									INSERT INTO predb (title, size, category, predate, source, md5, files)
									VALUES (%s, %s, %s, %s, %s, %s, %s)',
									$this->db->escapeString($matches2['title']),
									((!isset($matches2['size1']) && empty($matches2['size1']))
										? 'NULL'
										: $this->db->escapeString(round($matches2['size1']) . $matches2['size2'])
									),
									((isset($matches2['category']) && !empty($matches2['category']))
										? $this->db->escapeString($matches2['category'])
										: 'NULL'
									),
									$this->db->from_unixtime(strtotime($matches2['predate'])),
									$this->db->escapeString('zenet'),
									$md5,
									$this->db->escapeString($matches2['files'])))) {
								$newNames++;
							}
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Zenet failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre from pre list.
	 *
	 * @note - Probably has to be remade.
	 *
	 * @return int
	 */
	protected function retrievePrelist()
	{
		$newNames = 0;
		$buffer = $this->getUrl('http://www.prelist.ws/');
		if ($buffer !== false) {
			$matches = $matches2 = $matches3 = array();
			if (preg_match_all('/<div class="PreData ">.+?<\/div>\s*<\/div>\s*<\/div>/s', $buffer, $matches)) {
				foreach($matches as $matches2) {
					foreach ($matches2 as $matches3) {
						if (preg_match('/<a href=".+?">(?P<title>.+?)<\/a><\/div><div class="break".+?FiLTER">(?P<category>.+?)<\/a>.+?"Time">(?P<date>.+?)<\/div.+?"FilesSize">(?P<files>\d+F).*?(?P<size>\d+[KMGPT]?B).+?"Reason">(?P<reason>.*?)<\/div>/is', $matches3, $matches4)) {

							// Skip if too short.
							if (strlen($matches4['title']) < 15) {
								continue;
							}
							$md5 =  $this->db->escapeString(md5($matches4['title']));
							$oldName = $this->db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $md5));

							// If we have it already, skip.
							if ($oldName !== false) {
								continue;
							}

							$nuked = $nukereason = '';
							if (!empty($matches4['reason'])) {
								$nuked = IRCScraper::NUKE;
								$nukereason = $matches4['reason'];
							}

							if ($this->db->queryExec(
								sprintf('
										INSERT INTO predb (title, size, category, predate, source, md5, files, nuked, nukereason)
										VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)',
									$this->db->escapeString($matches4['title']),
									((!isset($matches4['size']) && empty($matches4['size']))
										? 'NULL'
										: $this->db->escapeString(round($matches4['size']))
									),
									$this->db->escapeString($matches4['category']),
									$this->db->from_unixtime(strtotime($matches4['date'])),
									$this->db->escapeString('prelist'),
									$md5,
									$this->db->escapeString($matches4['files']),
									($nuked === '' ? IRCScraper::NO_NUKE : $nuked),
									($nukereason === '' ? 'NULL' : $this->db->escapeString($nukereason))))) {
								$newNames++;
							}
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Prelist failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre from this source.
	 * @return int
	 */
	protected  function retrieveOrlydb()
	{
		$newNames = 0;
		$buffer = $this->getUrl('http://www.orlydb.com/');
		if ($buffer !== false) {
			$matches = $matches2 = $match = array();
			if (preg_match('/<div id="releases">(.+)<div id="pager">/s', $buffer, $match)) {
				if (preg_match_all('/<div>.+?<\/div>/s', $match["1"], $matches)) {
					foreach ($matches as $m1) {
						foreach ($m1 as $m) {
							if (preg_match('/timestamp">(?P<date>.+?)<\/span>.+?section">.+?">(?P<category>.+?)<\/a>.+?release">(?P<title>.+?)<\/span>(.+info">(?P<size>.+?) \| (?P<files>\d+F))?/s', $m, $matches2)) {

								// Skip if too short.
								if (strlen($matches2['title']) < 15) {
									continue;
								}
								$md5 = $this->db->escapeString(md5($matches2['title']));
								$oldName = $this->db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $md5));
								if ($oldName !== false) {
									continue;
								} elseif ($this->db->queryExec(
									sprintf('
										INSERT INTO predb (title, size, category, predate, source, md5, files)
										VALUES (%s, %s, %s, %s, %s, %s, %s)',
										$this->db->escapeString($matches2['title']),
										((!isset($matches2['size']) && empty($matches2['size']))
											? 'NULL'
											: $this->db->escapeString($matches2['size'])
										),
										$this->db->escapeString($matches2['category']),
										$this->db->from_unixtime(strtotime($matches2['date'])),
										$this->db->escapeString('orlydb'),
										$md5,
										((!isset($matches2['files']) && empty($matches2['files']))
											? 'NULL'
											: $this->db->escapeString($matches2['files']))
										))
									) {
									$newNames++;
								}
							}
						}
					}
				}
			}
		} else {
			if ($this->echooutput) {
				echo $this->c->error("Update from Orly failed.");
			}
		}
		return $newNames;
	}

	/**
	 * Get pre from this source.
	 * @return int
	 */
	protected function retrieveSrr()
	{
		$newNames = 0;

		$data = $this->getUrl("http://www.srrdb.com/feed/srrs");

		if ($data !== false) {
			$releases = @simplexml_load_string($data);
			if ($releases !== false) {
				foreach ($releases->channel->item as $release) {

					// If it's too short, skip.
					if (strlen($release->title) < 15) {
						continue;
					}
					$md5 = $this->db->escapeString(md5($release->title));
					$oldName = $this->db->queryOneRow(sprintf('SELECT id, nfo FROM predb WHERE md5 = %s', $md5));

					$nfo = $size = '';
					if (preg_match('/<dt>NFO availability<\/dt>\s*<dd>(?P<nfo>(yes|no))<\/dd>/is', $release->description, $description)) {
						$nfo = ($description['nfo'] === 'yes' ? $this->db->escapeString('srrdb') : 'NULL');
					}

					if (preg_match('/Filesize.*<td>(?P<size>\d*)<\/td>\s*<td>.*?<\/td>\s*<td>.*?<\/td>\s*<\/tr>\s*<\/table>\s*/is', $release->description, $description)) {
						$size = ((isset($description['size']) && !empty($description['size'])) ? $this->db->escapeString(nzedb\utility\bytesToSizeString($description['size'])) : 'NULL');
					}

					if ($oldName !== false) {
						if ($nfo !== '' && empty($oldName['nfo'])) {
							$this->db->queryExec(
								sprintf('
									UPDATE predb
									SET size = %s, predate = %s, source = %s, nfo = %s
									WHERE id = %d',
									$size,
									$this->db->from_unixtime(strtotime($release->pubDate)),
									$this->db->escapeString('srrdb'),
									$nfo,
									$oldName['id']
								)
							);
						}
						continue;
					} else if ($this->db->queryExec(
						sprintf('
							INSERT INTO predb (title, predate, source, md5, nfo, size)
							VALUES (%s, %s, %s, %s, %s, %s)',
							$this->db->escapeString($release->title),
							$this->db->from_unixtime(strtotime($release->pubDate)),
							$this->db->escapeString('srrdb'),
							$md5,
							$nfo,
							$size))) {
						$newNames++;
					}
				}
			} elseif ($this->echooutput) {
				echo $this->c->error("Update from Srr failed.");
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Srr failed.");
		}
		return $newNames;
	}

	/**
	 * Get pre from this source.
	 *
	 * @return int
	 */
	protected function retrievePredbme()
	{
		$newNames = 0;

		$URLs = array(
			'http://predb.me/?cats=movies-sd&rss=1',
			'http://predb.me/?cats=movies-hd&rss=1',
			'http://predb.me/?cats=movies-discs&rss=1',
			'http://predb.me/?cats=tv-sd&rss=1',
			'http://predb.me/?cats=tv-hd&rss=1',
			'http://predb.me/?cats=tv-discs&rss=1',
			'http://predb.me/?cats=music-audio&rss=1',
			'http://predb.me/?cats=music-video&rss=1',
			'http://predb.me/?cats=music-discs&rss=1',
			'http://predb.me/?cats=games-pc&rss=1',
			'http://predb.me/?cats=games-xbox&rss=1',
			'http://predb.me/?cats=games-playstation&rss=1',
			'http://predb.me/?cats=games-nintendo&rss=1',
			'http://predb.me/?cats=apps-windows&rss=1',
			'http://predb.me/?cats=apps-linux&rss=1',
			'http://predb.me/?cats=apps-mac&rss=1',
			'http://predb.me/?cats=apps-mobile&rss=1',
			'http://predb.me/?cats=books-ebooks&rss=1',
			'http://predb.me/?cats=books-audio-books&rss=1',
			'http://predb.me/?cats=xxx-videos&rss=1',
			'http://predb.me/?cats=xxx-images&rss=1',
			'http://predb.me/?cats=dox&rss=1',
			'http://predb.me/?cats=unknown&rss=1'
		);

		foreach ($URLs as &$url) {
			$data = $this->getUrl($url);
			if ($data !== false) {
				$releases = @simplexml_load_string($data);
				if ($releases !== false) {
					foreach ($releases->channel->item as $release) {

						// Skip if too short.
						if (strlen($release->title) < 15) {
							continue;
						}
						$md5 = $this->db->escapeString(md5($release->title));
						$oldname = $this->db->queryOneRow(sprintf('SELECT md5 FROM predb WHERE md5 = %s', $md5));
						if ($oldname !== false) {
							continue;
						} elseif ($this->db->queryExec(
							sprintf('
								INSERT INTO predb (title, predate, source, md5)
								VALUES (%s, NOW(), %s, %s)',
								$this->db->escapeString($release->title),
								$this->db->escapeString('predbme'),
								$md5))) {
							$newNames++;
						}
					}
				} else {
					if ($this->echooutput) {
						echo $this->c->error("Update from Predbme failed.");
					}
					// If the site is down, don't try the other URLs.
					break;
				}
			} else {
				if ($this->echooutput) {
					echo $this->c->error("Update from Predbme failed.");
				}
				// If the site is down, don't try the other URLs.
				break;
			}
		}
		return $newNames;
	}

	/**
	 * Get pre from this source.
	 *
	 * @return int
	 */
	protected function retrieveAllfilledMoovee()
	{
		$newNames = 0;
		$groupid = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.moovee'");

		if ($groupid === false) {
			return 0;
		} else {
			$groupid = $groupid['id'];
		}

		$buffer = $this->getUrl('http://abmoovee.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			$matches = $matches2 = $match = array();
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2)) {
							if (isset($matches2["requestid"]) && isset($matches2["title"])) {

								// If too short, skip.
								if (strlen($matches2["title"]) < 15) {
									continue;
								}
								$md5 = $this->db->escapeString(md5($matches2["title"]));

								$dupeCheck = $this->db->queryOneRow(sprintf('SELECT id, requestid FROM predb WHERE md5 = %s', $md5));
								if ($dupeCheck === false) {
									$this->db->queryExec(
										sprintf("
											INSERT INTO predb (title, predate, source, md5, requestid, groupid, files, category)
											VALUES (%s, %s, %s, %s, %s, %d, %s, 'Movies')",
											$this->db->escapeString($matches2["title"]),
											$this->db->from_unixtime(strtotime($matches2["predate"])),
											$this->db->escapeString('abMooVee'),
											$md5,
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2['files'])
										)
									);
									$newNames++;
								} else if (empty($dupeCheck['requestid'])) {
									$this->db->queryExec(
										sprintf('
											UPDATE predb
											SET requestid = %s, groupid = %d, files = %s
											WHERE md5 = %s',
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2['files']),
											$md5
										)
									);
								}
							}
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Moovee failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre for this source.
	 *
	 * @return int
	 */
	protected function retrieveAllfilledTeevee()
	{
		$newNames = 0;
		$groupid = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.teevee'");

		if ($groupid === false) {
			return 0;
		} else {
			$groupid = $groupid['id'];
		}

		$buffer = $this->getUrl('http://abteevee.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			$matches = $matches2 = $match = array();
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2)) {
							if (isset($matches2["requestid"]) && isset($matches2["title"])) {

								// Skip if too short.
								if (strlen($matches2["title"]) < 15) {
									continue;
								}
								$md5 = $this->db->escapeString(md5($matches2["title"]));
								$dupeCheck = $this->db->queryOneRow(sprintf('SELECT id, requestid FROM predb WHERE md5 = %s', $md5));
								if ($dupeCheck === false) {
									$this->db->queryExec(
										sprintf("
											INSERT INTO predb (title, predate, source, md5, requestid, groupid, files, category)
											VALUES (%s, %s, %s, %s, %s, %d, %s, 'TV')",
											$this->db->escapeString($matches2["title"]),
											$this->db->from_unixtime(strtotime($matches2["predate"])),
											$this->db->escapeString('abTeeVee'),
											$md5,
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2['files'])
										)
									);
									$newNames++;
								} else if (empty($dupeCheck['requestid'])) {
									$this->db->queryExec(
										sprintf('
											UPDATE predb
											SET requestid = %s, groupid = %d, files = %s
											WHERE md5 = %s',
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2['files']),
											$md5
										)
									);
								}
							}
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Teevee failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre for this source.
	 *
	 * @return int
	 */
	protected function retrieveAllfilledErotica()
	{
		$newNames = 0;
		$groupid = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.erotica'");

		if ($groupid === false) {
			return 0;
		} else {
			$groupid = $groupid['id'];
		}

		$buffer = $this->getUrl('http://aberotica.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			$matches = $matches2 = $match = array();
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+?<td class="cell_type">(?P<category>.+?)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2)) {
							if (isset($matches2["requestid"]) && isset($matches2["title"])) {

								// If too short, skip.
								if (strlen($matches2["title"]) < 15) {
									continue;
								}
								$md5 = $this->db->escapeString(md5($matches2["title"]));

								$dupeCheck = $this->db->queryOneRow(sprintf('SELECT id, requestid FROM predb WHERE md5 = %s', $md5));
								if ($dupeCheck === false) {
									$this->db->queryExec(
										sprintf("
											INSERT INTO predb (title, predate, source, md5, requestid, groupid, files, category)
											VALUES (%s, %s, %s, %s, %s, %d, %s, %s)",
											$this->db->escapeString($matches2["title"]),
											$this->db->from_unixtime(strtotime($matches2["predate"])),
											$this->db->escapeString('abErotica'),
											$md5,
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2['files']),
											$this->db->escapeString('XXX-' . $matches2['category'])
										)
									);
									$newNames++;
								} else if (empty($dupeCheck['requestid'])) {
									$this->db->queryExec(
										sprintf('
											UPDATE predb
											SET requestid = %s, groupid = %d, files = %s
											WHERE md5 = %s',
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2['files']),
											$md5
										)
									);
								}
							}
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Erotica failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre for this source.
	 *
	 * @return int
	 */
	protected function retrieveAllfilledForeign()
	{
		$newNames = 0;
		$groupid = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.mom'");

		if ($groupid === false) {
			return 0;
		} else {
			$groupid = $groupid['id'];
		}

		$buffer = $this->getUrl('http://abforeign.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				$matches = $matches2 = $match = array();
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+<td class="cell_type">(?P<category>.+?)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<predate>\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?)<\/td>/s', $m, $matches2)) {
							if (isset($matches2["requestid"]) && isset($matches2["title"])) {

							// If too short, skip.
							if (strlen($matches2["title"]) < 15) {
								continue;
							}
							$md5 = $this->db->escapeString(md5($matches2["title"]));

								$dupeCheck = $this->db->queryOneRow(sprintf('SELECT id, requestid FROM predb WHERE md5 = %s', $md5));
								if ($dupeCheck === false) {
									$this->db->queryExec(
										sprintf("
											INSERT INTO predb (title, predate, source, md5, requestid, groupid, files, category)
											VALUES (%s, %s, %s, %s, %s, %d, %s, %s)",
											$this->db->escapeString($matches2["title"]),
											$this->db->from_unixtime(strtotime($matches2["predate"])),
											$this->db->escapeString('abForeign'),
											$md5,
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2["files"]),
											$this->db->escapeString($matches2["category"])
										)
									);
									$newNames++;
								} else if (empty($dupeCheck['requestid'])) {
									$this->db->queryExec(
										sprintf('
											UPDATE predb
											SET requestid = %s, groupid = %d, files = %s, category = %s
											WHERE md5 = %s',
											$matches2["requestid"],
											$groupid,
											$this->db->escapeString($matches2["files"]),
											$this->db->escapeString($matches2["category"]),
											$md5
										)
									);
								}
							}
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Foreign failed.");
		}
		return $newNames;
	}

	/**
	 * Get new pre for this source.
	 *
	 * @return int
	 */
	protected function retrieveAbgx()
	{
		$newnames = 0;
		$groups = new Groups();
		$groupname = $request = $title = '';


		$arr = array('x360', 'abcp', 'abgw', 'abgwu', 'absp', 'abgn', 'spsv', 'n3ds', 'abgx', 'abg', 'x360');
		foreach ($arr as &$value) {
			$data = $this->getUrl('http://www.abgx.net/rss/' . $value . '/posted.rss');
			if ($data !== false) {
				$releases = @simplexml_load_string($data);
				if ($releases !== false) {
					preg_match('/^Filled requests in #(\S+)/', $releases->channel->description, $groupname);
					$groupid = ($groups->getIDByName($groupname[1])) ? $groups->getIDByName($groupname[1]) : 0;
					foreach ($releases->channel->item as $release) {

						preg_match('/\(\d+\) (\S+) .+(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2})/', $release->description, $title);
						// Skip if too short.
						if (strlen($title[1]) < 15) {
							continue;
						}
						preg_match('/^Req (\d+) - (\S+) .+/', $release->title, $request);
						$requestid = $request[1];
						$md5 = md5($title[1]);
						$predate = $title[2];

						$oldname = $this->db->queryOneRow(sprintf('SELECT md5, requestid, groupid FROM predb WHERE md5 = %s', $this->db->escapeString($md5)));
						if ($oldname !== false && empty($oldname['requestid'])) {
							$this->db->queryExec(
								sprintf('
									UPDATE predb
									SET requestid = %d, groupid = %d
									WHERE md5 = %s',
									max($oldname['requestid'], $requestid),
									max($oldname['groupid'], $groupid),
									$this->db->escapeString($md5)
								)
							);
						}
						else if ($oldname === false) {
							if ($this->db->queryExec(
								sprintf('
									INSERT INTO predb (title, predate, source, md5, requestid, groupid)
									VALUES (%s, %s, %s, %s, %d, %d)',
									$this->db->escapeString($title[1]),
									$this->db->from_unixtime(strtotime($predate)),
									$this->db->escapeString('abgx'),
									$this->db->escapeString($md5),
									$requestid,
									$groupid))) {
								$newnames++;
							}
						}
					}
				} else {
					if ($this->echooutput) {
						echo $this->c->error("Update from ABGX failed.");
					}
					return $newnames;
				}
			} else {
				if ($this->echooutput) {
					echo $this->c->error("Update from ABGX failed.");
				}
				return $newnames;
			}
		}
		return $newnames;
	}

	/**
	 * Get new pre for this source.
	 *
	 * @return int
	 */
	protected  function retrieveUsenetCrawler()
	{
		$db = new DB();
		$newnames = 0;

		$data = $this->getUrl("http://www.usenet-crawler.com/predb?q=&c=&offset=0#results");
		if ($data === false) {
			return 0;
		}

		$html = str_get_html($data);
		$releases = @$html->find('table[id="browsetable"]');
		if (!isset($releases[0])) {
			return $newnames;
		}
		$rows = $releases[0]->find('tr');
		$count = 0;
		foreach ($rows as $post) {
			if ($count == 0) {
				//Skip the table header row
				$count++;
				continue;
			}
			$data = $post->find('td');
			$predate = strtotime($data[0]->innertext);

			$e = $data[1]->find('a');
			if (isset($e[0])) {
				$title = trim($e[0]->innertext);
				$title = str_ireplace(array('<u>', '</u>'), '', $title);
			} elseif (preg_match('/(.+)<\/br><sub>/', $data[1])) {
				// title is nuked, so skip
				continue;
			} else {
				$title = trim($data[1]->innertext);
			}

			$md5 = md5($title);
			// Check DB if we already have it.
			$check = $db->queryOneRow(sprintf('SELECT id FROM predb WHERE md5 = %s', $db->escapeString($md5)));
			if ($check !== false) {
				continue;
			}

			$e = $data[2]->find('a');
			$category = $e[0]->innertext;
			preg_match('/([\d\.]+MB)/', $data[3]->innertext, $match);
			$size = isset($match[1]) ? $match[1] : 'NULL';
			if (strlen($title) > 15 && $category != 'NUKED') {
				if ($db->queryExec(sprintf('INSERT INTO predb (title, predate, source, md5, category, size) VALUES (%s, %s, %s, %s, %s, %s)', $db->escapeString($title), $db->from_unixtime($predate), $db->escapeString('usenet-crawler'), $db->escapeString($md5), $db->escapeString($category), $db->escapeString($size)))) {
					$newnames++;
				}
			}
		}
		return $newnames;
	}

	// Update a single release as it's created.
	public function matchPre($cleanerName, $releaseID)
	{
		$db = new DB();
		$x = $db->queryOneRow(sprintf('SELECT id FROM predb WHERE title = %s', $db->escapeString($cleanerName)));
		if (isset($x['id'])) {
			$db->queryExec(sprintf('UPDATE releases SET preid = %d WHERE id = %d', $x['id'], $releaseID));
			return true;
		}
		return false;
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
		if ($this->echooutput) {
			echo $this->c->header('Querying DB for release searchnames not matched with preDB titles.');
		}

		$res = $db->queryDirect('SELECT p.id AS preid, r.id AS releaseid FROM predb p INNER JOIN releases r ON p.title = r.searchname WHERE r.preid = 0');
		$total = $res->rowCount();
		echo $this->c->primary(number_format($total) . ' releases to match.');
		if ($total > 0) {
			foreach ($res as $row) {
				$db->queryExec(sprintf('UPDATE releases SET preid = %d WHERE id = %d', $row['preid'], $row['releaseid']));
				if ($this->echooutput) {
					$consoletools->overWritePrimary('Matching up preDB titles with release searchnames: ' . $consoletools->percentString( ++$updated, $total));
				}
			}
			if ($this->echooutput) {
				echo "\n";
			}
		}
		return $updated;
	}

	// Look if the release is missing an nfo.
	public function matchNfo($nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(binaries->updateAllGroups).\n"));
		}

		$db = new DB();
		$nfos = 0;
		if ($this->echooutput) {
			echo $this->c->primary('Matching up predb NFOs with releases missing an NFO.');
		}

		$res = $db->queryDirect('SELECT r.id, p.nfo, p.title, r.completion, r.guid, r.groupid FROM releases r INNER JOIN predb p ON r.preid = p.id WHERE r.nfostatus != 1 AND p.nfo IS NOT NULL LIMIT 100');
		$total = $res->rowCount();
		if ($total > 0) {
			$nfo = new Nfo($this->echooutput);
			foreach ($res as $row) {
				$URL = $row['nfo'];

				// To save space in the DB we do this instead of storing the full URL.
				if ($URL === 'srrdb') {
					$URL = 'http://www.srrdb.com/download/file/' . $row['title'] . '/' . strtolower(urlencode($row['title'])) . '.nfo';
				}

				$buffer = $this->getUrl($URL);

				if ($buffer !== false) {
					if (strlen($buffer) < 5) {
						continue;
					}

					if ($row['nfo'] === 'srrdb' && preg_match('/You\'ve reached the daily limit/i', $buffer)) {
						continue;
					}

					if ($nfo->addAlternateNfo($db, $buffer, $row, $nntp)) {
						if ($this->echooutput) {
							echo '+';
						}
						$nfos++;
					} else {
						if ($this->echooutput) {
							echo '-';
						}
					}
				}
			}
		}
		return $nfos;
	}

	// Matches the MD5 within the predb table to release files and subjects (names) which are hashed.
	public function parseTitles($time, $echo, $cats, $namestatus, $show)
	{
		$db = new DB();
		$namefixer = new NameFixer($this->echooutput);
		$consoletools = new ConsoleTools();
		$updated = $checked = 0;
		$matches = '';

		$tq = '';
		if ($time == 1) {
			if ($db->dbSystem() === 'mysql') {
				$tq = 'AND r.adddate > (NOW() - INTERVAL 3 HOUR) ORDER BY rf.releaseid, rf.size DESC';
			} else if ($db->dbSystem() === 'pgsql') {
				$tq = "AND r.adddate > (NOW() - INTERVAL '3 HOURS') ORDER BY rf.releaseid, rf.size DESC";
			}
		}
		$ct = '';
		if ($cats == 1) {
			$ct = 'AND r.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050)';
		}

		if ($this->echooutput) {
			$te = '';
			if ($time == 1) {
				$te = ' in the past 3 hours';
			}
			echo $this->c->header('Fixing search names' . $te . " using the predb md5.");
		}
		if ($db->dbSystem() === 'mysql') {
			$regex = "AND (r.ishashed = 1 OR rf.name REGEXP'[a-fA-F0-9]{32}')";
		} else if ($db->dbSystem() === 'pgsql') {
			$regex = "AND (r.ishashed = 1 OR rf.name ~ '[a-fA-F0-9]{32}')";
		}

		if ($cats === 3) {
			$query = sprintf('SELECT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.groupid, '
				. 'dehashstatus, rf.name AS filename FROM releases r '
				. 'LEFT OUTER JOIN releasefiles rf ON r.id = rf.releaseid '
				. 'WHERE nzbstatus = 1 AND dehashstatus BETWEEN -6 AND 0 AND preid = 0 %s', $regex);
		} else {
			$query = sprintf('SELECT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.groupid, '
				. 'dehashstatus, rf.name AS filename FROM releases r '
				. 'LEFT OUTER JOIN releasefiles rf ON r.id = rf.releaseid '
				. 'WHERE nzbstatus = 1 AND isrenamed = 0 AND dehashstatus BETWEEN -6 AND 0 %s %s %s', $regex, $ct, $tq);
		}

		$res = $db->queryDirect($query);
		$total = $res->rowCount();
		echo $this->c->primary(number_format($total) . " releases to process.");
		if ($total > 0) {
			foreach ($res as $row) {
				if (preg_match('/[a-f0-9]{32}/i', $row['name'], $matches)) {
					$updated = $updated + $namefixer->matchPredbMD5($matches[0], $row, $echo, $namestatus, $this->echooutput, $show);
				} else if (preg_match('/[a-f0-9]{32}/i', $row['filename'], $matches)) {
					$updated = $updated + $namefixer->matchPredbMD5($matches[0], $row, $echo, $namestatus, $this->echooutput, $show);
				}
				if ($show === 2) {
					$consoletools->overWritePrimary("Renamed Releases: [" . number_format($updated) . "] " . $consoletools->percentString(++$checked, $total));
				}
			}
		}
		if ($echo == 1) {
			echo $this->c->header("\n" . $updated . " releases have had their names changed out of: " . number_format($checked) . " files.");
		} else {
			echo $this->c->header("\n" . $updated . " releases could have their names changed. " . number_format($checked) . " files were checked.");
		}

		return $updated;
	}

	/**
	 * @param int    $offset  OFFSET
	 * @param int    $offset2 LIMIT
	 * @param string $search  Optional title search.
	 *
	 * @return array The row count and the query results.
	 */
	public function getAll($offset, $offset2, $search = '')
	{
		$db = new DB();
		if ($search !== '') {
			$like = ($db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE');
			$search = explode(' ', trim($search));
			if (count($search > 1)) {
				$search = "$like '%" . implode("%' AND title $like '%", $search) . "%'";
			} else {
				$search = "$like '%" . $search . "%'";
			}
			$search = 'WHERE title ' . $search;
			$count = $db->queryOneRow(sprintf('SELECT COUNT(*) AS cnt FROM predb %s', $search));
			$count = $count['cnt'];
		} else {
			$count = $this->getCount();
		}

		$parr = $db->query(sprintf('SELECT p.*, r.guid FROM predb p LEFT OUTER JOIN releases r ON p.id = r.preid %s ORDER BY p.predate DESC LIMIT %d OFFSET %d', $search, $offset2, $offset));
		return array('arr' => $parr, 'count' => $count);
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

	/**
	 * Get data from URL.
	 *
	 * @param string $url
	 *
	 * @return bool|string
	 */
	protected function getUrl($url)
	{
		return nzedb\utility\getUrl(
			$url,
			'get',
			'',
			'en',
			false,
			'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) ' .
			'Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10', 'foo=bar'
		);
	}

}
