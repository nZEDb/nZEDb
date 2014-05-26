<?php
require_once nZEDb_LIBS . "simple_html_dom.php";

use nzedb\db\DB;
use nzedb\utility;

/**
 * Class for inserting names/categories etc from PreDB sources into the DB,
 * also for matching names on files / subjects.
 *
 * Class PreDb
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

	// Nuke status.
	const PRE_NONUKE  = 0; // Pre is not nuked.
	const PRE_UNNUKED = 1; // Pre was un nuked.
	const PRE_NUKED   = 2; // Pre is nuked.
	const PRE_MODNUKE = 3; // Nuke reason was modified.
	const PRE_RENUKED = 4; // Pre was re nuked.
	const PRE_OLDNUKE = 5; // Pre is nuked for being old.

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
	 * Update PRE for a current source.
	 * @var int
	 */
	protected $updatedPre;

	/**
	 * Inserted PRE for a current source.
	 * @var int
	 */
	protected $insertedPre;

	/**
	 * @param bool $echo
	 */
	public function __construct($echo = false)
	{
		$this->echooutput = ($echo && nZEDb_ECHOCLI);
		$this->db = new DB();
		$this->c = new ColorCLI();
		$this->updatedPre = 0;
		$this->insertedPre = 0;
	}

	/**
	 * Retrieve pre info from PreDB sources and store them in the DB.
	 *
	 * @return int The quantity of new titles retrieved.
	 */
	public function updatePre()
	{
		$newPre = $updatedPre = 0;
		$newestRel = $this->db->queryOneRow("SELECT value AS adddate FROM settings WHERE setting = 'lastpretime'");

		// Wait 10 minutes in between pulls.
		if ((int)$newestRel['adddate'] < (time() - 600)) {

			if ($this->echooutput) {
				echo $this->c->header("Retrieving titles from preDB sources.");
			}

			if (self::PRE_WOMBLE) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveWomble();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Womble.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Womble.");
				}
			}

			if (self::PRE_OMGWTF) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveOmgwtfnzbs();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Omgwtfnzbs.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Omgwtfnzbs.");
				}
			}

			if (self::PRE_ZENET) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveZenet();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Zenet.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Zenet.");
				}
			}

			if (self::PRE_PRELIST) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrievePrelist();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Prelist.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Prelist.");
				}
			}

			if (self::PRE_ORLYDB) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveOrlydb();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Orlydb.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Orlydb.");
				}
			}

			if (self::PRE_SRRDB) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveSrr();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Srrdb.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Srrdb.");
				}
			}

			if (self::PRE_PREDBME) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrievePredbme();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Predbme.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Predbme.");
				}
			}

			if (self::PRE_ABGXNET) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveAbgx();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from abgx.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from abgx.");
				}
			}

			if (self::PRE_UCRAWLER) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveUsenetCrawler();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Usenet-Crawler.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Usenet-Crawler.");
				}
			}

			if (self::PRE_MOOVEE) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveAllfilledMoovee();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Allfilled Moovee.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Allfilled Moovee.");
				}
			}

			if (self::PRE_TEEVEE) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveAllfilledTeevee();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Allfilled Teevee.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Allfilled Teevee.");
				}
			}

			if (self::PRE_EROTICA) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveAllfilledErotica();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Allfilled Erotica.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Allfilled Erotica.");
				}
			}

			if (self::PRE_FOREIGN) {
				$this->updatedPre = $this->insertedPre = 0;
				$this->retrieveAllfilledForeign();
				$newPre += $this->insertedPre;
				$updatedPre += $this->updatedPre;
				if ($this->echooutput) {
					echo $this->c->primary($this->insertedPre . " \tRetrieved from Allfilled Foreign.");
					echo $this->c->primary($this->updatedPre . " \tUpdated from Allfilled Foreign.");
				}
			}

			if ($this->echooutput) {
				echo $this->c->primary($newPre . " \tRetrieved from all sources.");
				echo $this->c->primary($updatedPre . " \tUpdated from all sources.");
			}

			// If we found nothing, update the last added to now to reset the timer.
			$this->db->queryExec(sprintf("UPDATE settings SET value = %s WHERE setting = 'lastpretime'", $this->db->escapeString(time())));
		}

		return $newPre;
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

	protected function verifyPreData(&$matches)
	{
		// If the title is too short, don't bother.
		if (strlen($matches['title']) < 15) {
			return;
		}

		$matches['title'] = str_replace(array("\r", "\n"), '', $matches['title']);

		$duplicateCheck = $this->db->queryOneRow(sprintf('SELECT id, nfo, size, category FROM predb WHERE title = %s', $this->db->escapeString($matches['title'])));

		if ($duplicateCheck === false) {
			$this->db->queryExec(
				sprintf('
					INSERT INTO predb (title, nfo, size, category, predate, source, requestid, groupid, files, filename, nuked, nukereason)
					VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %s, %s, %d, %s)',
					$this->db->escapeString($matches['title']),
					((isset($matches['nfo']) && !empty($matches['nfo'])) ? $this->db->escapeString($matches['nfo']) : 'NULL'),
					((isset($matches['size']) && !empty($matches['size'])) ? $this->db->escapeString($matches['size']) : 'NULL'),
					((isset($matches['category']) && !empty($matches['category'])) ? $this->db->escapeString($matches['category']) : 'NULL'),
					$this->db->from_unixtime($matches['date']),
					$this->db->escapeString($matches['source']),
					((isset($matches['requestid']) && is_numeric($matches['requestid']) ? $matches['requestid'] : 0)),
					((isset($matches['groupid']) && is_numeric($matches['groupid'])) ? $matches['groupid'] : 0),
					((isset($matches['files']) && !empty($matches['files'])) ? $this->db->escapeString($matches['files']) : 'NULL'),
					(isset($matches['filename']) ? $this->db->escapeString($matches['filename']) : $this->db->escapeString('')),
					((isset($matches['nuked']) && is_numeric($matches['nuked'])) ? $matches['nuked'] : 0),
					((isset($matches['reason']) && !empty($matches['nukereason'])) ? $this->db->escapeString($matches['nukereason']) : 'NULL')
				)
			);
			$this->insertedPre++;
		} else {
			if (empty($matches['title'])) {
				return;
			}

			$query = 'UPDATE predb SET ';

			$query .= (!empty($matches['nfo'])       ? 'nfo = '        . $this->db->escapeString($matches['nfo'])      . ', ' : '');
			$query .= (!empty($matches['size'])      ? 'size = '       . $this->db->escapeString($matches['size'])     . ', ' : '');
			$query .= (!empty($matches['source'])    ? 'source = '     . $this->db->escapeString($matches['source'])   . ', ' : '');
			$query .= (!empty($matches['files'])     ? 'files = '      . $this->db->escapeString($matches['files'])    . ', ' : '');
			$query .= (!empty($matches['reason'])    ? 'nukereason = ' . $this->db->escapeString($matches['reason'])   . ', ' : '');
			$query .= (!empty($matches['requestid']) ? 'requestid = '  . $matches['requestid']                         . ', ' : '');
			$query .= (!empty($matches['groupid'])   ? 'groupid = '    . $matches['groupid']                           . ', ' : '');
			$query .= (!empty($matches['predate'])   ? 'predate = '    . $matches['predate']                           . ', ' : '');
			$query .= (!empty($matches['nuked'])     ? 'nuked = '      . $matches['nuked']                             . ', ' : '');
			$query .= (!empty($matches['filename'])  ? 'filename = '   . $this->db->escapeString($matches['filename']) . ', ' : '');
			$query .= (
				(empty($duplicateCheck['category']) && !empty($matches['category']))
					? 'category = ' . $this->db->escapeString($matches['category']) . ', '
					: ''
			);

			if ($query === 'UPDATE predb SET '){
				return;
			}

			$query .= 'title = '      . $this->db->escapeString($matches['title']);
			$query .= ' WHERE title = ' . $this->db->escapeString($matches['title']);

			$this->db->queryExec($query);

			$this->updatedPre++;
		}
	}

	/**
	 * Retrieve new pre info from womble.
	 */
	protected function retrieveWomble()
	{
		$buffer = $this->getUrl('http://www.newshost.co.za');
		if ($buffer !== false) {
			if (preg_match_all('/<tr bgcolor=#[df]{6}>.+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<tr bgcolor=#[df]{6}>.+?<td>(?P<date>.+?)<\/td>(.+?right>(?P<size1>.+?)&nbsp;(?P<size2>.+?)<\/td.+?)?<td>(?P<category>.+?)<\/td.+?<a href=.+?(<a href="(?P<nfo>.+?)">nfo<\/a>.+)?<td>(?P<title>.+?)<\/td.+tr>/s', $m, $matches2)) {

							$matches2['size'] =
								((!isset($matches['size1']) && empty($matches2['size1']))
									? ''
									: $matches2['size1'] . $matches2['size2']
								);

							$matches2['nfo'] =
								($matches2['nfo'] == ''
									? ''
									: 'http://www.newshost.co.za/' . $matches2['nfo']
								);

							$matches2['source'] = 'womble';
							$matches2['date'] = strtotime($matches2['date']);
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Womble failed.");
		}
	}

	/**
	 * Retrieve pre info from omg.
	 */
	protected function retrieveOmgwtfnzbs()
	{
		$buffer = $this->getUrl('http://rss.omgwtfnzbs.org/rss-info.php');
		if ($buffer !== false) {
			if (preg_match_all('/<item>.+?<\/item>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<title>(?P<title>.+?)\s+-\s+omgwtfnzbs\.org.*?<\/title.+?pubDate>(?P<date>.+?)<\/pubDate.+?gory:<\/b> (?P<category>.+?)<br \/.+?<\/b> (?P<size1>.+?) (?P<size2>[a-zA-Z]+)<b/s', $m, $matches2)) {

							$matches2['size'] = (round($matches2['size1']) . $matches2['size2']);
							$matches2['source'] = 'omgwtfnzbs';
							$matches2['date'] = strtotime($matches2['date']);
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this ->echooutput) {
			echo $this->c->error("Update from Omgwtfnzbs failed.");
		}
	}

	/**
	 * Get new pre data from zenet.
	 *
	 * @return int
	 */
	protected function retrieveZenet()
	{
		$buffer = $this->getUrl('http://pre.zenet.org/live.php');
		if ($buffer !== false) {
			if (preg_match_all('/<div class="mini-layout fluid">.+?<\/div>\s*<\/div>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<span class="bold">(?P<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2})<\/span>.+<a href="\?post=\d+"><b><font color="#\d+">(?P<title>.+)<\/font><\/b><\/a>.+<p><a href="\?cats=.+"><font color="#FF9900">(?P<category>.+)<\/font><\/a> \| (?P<size1>[\d\.,]+)?(?P<size2>[MGK]B)? \/\s+(?P<files>\d+).+<\/div>/s', $m, $matches2)) {

							$matches2['size'] = (round($matches2['size1']) . $matches2['size2']);
							$matches2['source'] = 'zenet';
							$matches2['date'] = strtotime($matches2['date']);
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Zenet failed.");
		}
	}

	/**
	 * Get new pre from pre list.
	 */
	protected function retrievePrelist()
	{
		$buffer = $this->getUrl('http://www.prelist.ws/');
		if ($buffer !== false) {
			if (preg_match_all('/<tt id="\d+"><small><span class=".+?">.+?<\/span><\/small><br\/><\/tt>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match(
							'/">\[\s*(?P<date>\d+\.\d+\.(19|20)\d{2}.+?UTC).+?section=\s*(?P<category>.+?)\s*".+?<a href="\?search=.+?">\s*(?P<title>.+?)\s*<\/a>.+?<b>\[\s*(?P<size>\d+(\.\d+)?[KMGTP]?B)\s*\]<\/b>.+?<b>\[\s*(?P<files>\d+F)\s*\]<\/b>/i',
							$m, $result))
						{
							$result['source'] = 'prelist';
							$result['date'] = strtotime($result['date']);
							$this->verifyPreData($result);
						} else if (preg_match(
							'/">\[\s*(?P<date>\d+\.\d+\.(19|20)\d{2}.+?UTC).+?<a title="\s*(?P<reason>.+?)\s*">\s*(?P<nuked>(UN)?NUKED)\s*<\/a>.+?section=\s*(?P<category>.+?)\s*".+?<a href="\?search=.+?">\s*(?P<title>.+?)\s*<\/a>/',
							$m, $result))
						{
							$result['source'] = 'prelist';
							$result['date'] = strtotime($result['date']);
							$result['nuked'] = ($result['nuked'] === 'UNNUKED' ? PreDb::PRE_UNNUKED : PreDb::PRE_NUKED);
							$this->verifyPreData($result);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Prelist failed.");
		}
	}

	/**
	 * Get new pre from OrlyDB.
	 */
	protected  function retrieveOrlydb()
	{
		$buffer = $this->getUrl('http://www.orlydb.com/');
		if ($buffer !== false) {
			if (preg_match('/<div id="releases">(.+)<div id="pager">/s', $buffer, $match)) {
				if (preg_match_all('/<div>.+?<\/div>/s', $match['1'], $matches)) {
					foreach ($matches as $m1) {
						foreach ($m1 as $m) {
							if (preg_match('/timestamp">(?P<date>.+?)<\/span>.+?section">.+?">(?P<category>.+?)<\/a>.+?release">(?P<title>.+?)<\/span>(.+info">(?P<size>.+?) \| (?P<files>\d+F))?/s', $m, $matches2)) {

								$matches2['source'] = 'orlydb';
								$matches2['date'] = strtotime($matches2['date']);
								$this->verifyPreData($matches2);
							}
						}
					}
				}
			}
		} else if ($this->echooutput) {
			echo $this->c->error("Update from Orly failed.");
		}
	}

	/**
	 * Get pre from SrrDB.
	 */
	protected function retrieveSrr()
	{
		$data = $this->getUrl("http://www.srrdb.com/feed/srrs");
		if ($data !== false) {
			$data = @simplexml_load_string($data);
			if ($data !== false) {
				foreach ($data->channel->item as $release) {
					$result = array();
					$result['title'] = $release->title;
					$result['date'] = strtotime($release->pubDate);
					$result['source'] = 'srrdb';
					if (preg_match('/<dt>NFO availability<\/dt>\s*<dd>(?P<nfo>(yes|no))<\/dd>/is', $release->description, $description)) {
						$result['nfo'] = ($description['nfo'] === 'yes' ? 'srrdb' : 'NULL');
					}
					$this->verifyPreData($result);
				}
			}
		}

		if ($this->echooutput && $data === false) {
			echo $this->c->error("Update from Srr failed.");
		}
	}

	/**
	 * Get pre from PreDBMe.
	 */
	protected function retrievePredbme()
	{
		$URLs = array(
			'http://predb.me/?cats=movies-sd',
			'http://predb.me/?cats=movies-hd',
			'http://predb.me/?cats=movies-discs',
			'http://predb.me/?cats=tv-sd',
			'http://predb.me/?cats=tv-hd',
			'http://predb.me/?cats=tv-discs',
			'http://predb.me/?cats=music-audio',
			'http://predb.me/?cats=music-video',
			'http://predb.me/?cats=music-discs',
			'http://predb.me/?cats=games-pc',
			'http://predb.me/?cats=games-xbox',
			'http://predb.me/?cats=games-playstation',
			'http://predb.me/?cats=games-nintendo',
			'http://predb.me/?cats=apps-windows',
			'http://predb.me/?cats=apps-linux',
			'http://predb.me/?cats=apps-mac',
			'http://predb.me/?cats=apps-mobile',
			'http://predb.me/?cats=books-ebooks',
			'http://predb.me/?cats=books-audio-books',
			'http://predb.me/?cats=xxx-videos',
			'http://predb.me/?cats=xxx-images',
			'http://predb.me/?cats=dox',
			'http://predb.me/?cats=unknown'
		);

		foreach ($URLs as &$url) {
			$data = $this->getUrl($url);
			if ($data !== false) {
				if (preg_match_all('/<div class="post" id="\d+">\s*<div class="p-head">.+?<\/a>\s*<\/div>\s*<\/div>\s*<\/div>/s', $data, $matches)) {
					foreach ($matches as $match) {
						foreach ($match as $m) {
							if (preg_match('/time"\s*data="(?P<time>\d+)".+?adult">(?P<cat1>.+?)<\/a>.+?child">(?P<cat2>.+?)<\/a>.+?title"\s*href=".+?">(?P<title>.+?)<\/a>(.+?Nuked:\s*(?P<nuked>.+?)">)?/i', $m, $result)) {

								$result['source'] = 'predbme';
								$result['category'] = ($result['cat1'] . '-' . $result['cat2']);
								$result['date'] = $result['time'];
								$result['nuked'] = (isset($result['nuked']) && !empty($result['nuked']) ? PreDb::PRE_NUKED : PreDb::PRE_NONUKE);
								$result['nukereason'] = (isset($result['nuked']) && !empty($result['nuked']) ? $result['nuked'] : '');
								$this->verifyPreData($result);
							}
						}
					}
				}
			} else {
				if ($this->echooutput) {
					echo $this->c->error("Update from Predbme failed.");
				}
				// If the site is down, don't try the other URLs.
				break;
			}
		}
	}

	/**
	 * Get pre from this abMooVee.
	 */
	protected function retrieveAllfilledMoovee()
	{
		$groupID = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.moovee'");
		if ($groupID === false) {
			return;
		}

		$buffer = $this->getUrl('http://abmoovee.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2)) {

							$matches2['source'] = 'abMooVee';
							$matches2['date'] = strtotime($matches2['date']);
							$matches2['groupid'] = $groupID['id'];
							$matches2['category'] = 'Movies';
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Moovee failed.");
		}
	}

	/**
	 * Get new pre for this abTeeVee.
	 */
	protected function retrieveAllfilledTeevee()
	{
		$groupID = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.teevee'");
		if ($groupID === false) {
			return;
		}

		$buffer = $this->getUrl('http://abteevee.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2)) {

							$matches2['source'] = 'abTeeVee';
							$matches2['date'] = strtotime($matches2['date']);
							$matches2['groupid'] = $groupID['id'];
							$matches2['category'] = 'TV';
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Teevee failed.");
		}
	}

	/**
	 * Get new pre for abErotica.
	 */
	protected function retrieveAllfilledErotica()
	{
		$groupID = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.erotica'");
		if ($groupID === false) {
			return;
		}

		$buffer = $this->getUrl('http://aberotica.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+?<td class="cell_type">(?P<category>.+?)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/s', $m, $matches2)) {

							$matches2['source'] = 'abErotica';
							$matches2['date'] = strtotime($matches2['date']);
							$matches2['groupid'] = $groupID['id'];
							$matches2['category'] = ('XXX-' . $matches2['category']);
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Erotica failed.");
		}
	}

	/**
	 * Get new pre for abForeign.
	 */
	protected function retrieveAllfilledForeign()
	{
		$groupID = $this->db->queryOneRow("SELECT id FROM groups WHERE name = 'alt.binaries.mom'");
		if ($groupID === false) {
			return;
		}

		$buffer = $this->getUrl('http://abforeign.allfilled.com/reqs.php?fetch=posted&page=1');
		if ($buffer !== false) {
			if (preg_match_all('/<tr class="(even|odd)".+?<\/tr>/s', $buffer, $matches)) {
				foreach ($matches as $match) {
					foreach ($match as $m) {
						if (preg_match('/<td class="cell_reqid">(?P<requestid>\d+)<\/td>.+<td class="cell_type">(?P<category>.+?)<\/td>.+?<td class="cell_filecount">(?P<files>\d+x\d+)<\/td>.+?<td class="cell_request">(?P<title>.+)<\/td>.+<td class="cell_statuschange">(?P<date>\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?)<\/td>/s', $m, $matches2)) {

							$matches2['source'] = 'abForeign';
							$matches2['date'] = strtotime($matches2['date']);
							$matches2['groupid'] = $groupID['id'];
							$this->verifyPreData($matches2);
						}
					}
				}
			}
		} elseif ($this->echooutput) {
			echo $this->c->error("Update from Foreign failed.");
		}
	}

	/**
	 * Get new pre for ABGX.
	 */
	protected function retrieveAbgx()
	{
		$data = true;
		$channels = array(
			'alt.binaries.games.xbox360'     => array('source' => 'x360', 'cat' => 'X360'),
			'alt.binaries.console.ps3'       => array('source' => 'abcp', 'cat' => 'PS3'),
			'alt.binaries.games.wii'         => array('source' => 'abgw', 'cat' => 'WII'),
			'alt.binaries.games.wiiu'        => array('source' => 'abgwu', 'cat' => 'WIIU'),
			'alt.binaries.sony.psp'          => array('source' => 'absp', 'cat' => 'PSP'),
			'alt.binaries.games.nintendods'  => array('source' => 'abgn', 'cat' => 'NDS'),
			'alt.binaries.sony.psvita'       => array('source' => 'spsv', 'cat' => 'PSVita'),
			'alt.binaries.games.nintendo3ds' => array('source' => 'n3ds', 'cat' => '3DS'),
			'alt.binaries.games.xbox'        => array('source' => 'abgx', 'cat' => 'XBOX'),
			'alt.binaries.gamecube'          => array('source' => 'abg', 'cat' => 'NGC')
		);

		foreach ($channels as $group => &$channel) {
			$data = $this->getUrl('http://www.abgx.net/rss/' . $channel['source'] . '/posted.rss');
			if ($data !== false) {
				$data = @simplexml_load_string($data);
				if ($data !== false) {
					$groupID = $this->db->queryOneRow(sprintf('SELECT id FROM groups WHERE name = %s', $this->db->escapeString($group)));
					if ($groupID === false) {
						$groupID = 0;
					} else {
						$groupID = $groupID['id'];
					}

					foreach ($data->channel->item as $release) {

						if (preg_match('/\(\d+\)\s*(?P<title>\S*)\s*.+?\s*.+?\s*(?P<filename>\S*)\s*(?P<size1>\d+)x(?P<size2>\d+)MB.+?(?P<date>\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2})/', $release->description, $matches)) {

							if (preg_match('/^Req (\d+) - (\S+) .+/', $release->title, $request)) {
								$matches['requestid'] = $request[1];
							}
							$matches['date'] = strtotime($matches['date']);
							$matches['source'] = 'abgx';
							$matches['groupid'] = $groupID;
							$matches['category'] = $channel['cat'];
							$matches['size'] = (round($matches['size1'] * $matches['size2']) . 'MB');
							$matches['files'] = ($matches['size1'] . 'x' . $matches['size2'] . 'MB');
							$this->verifyPreData($matches);
						}
					}
				} else {
					break;
				}
			} else {
				break;
			}
		}

		if ($data === false && $this->echooutput) {
			echo $this->c->error("Update from ABGX failed.");
		}
	}

	/**
	 * Get new pre for Usenet Crawler.
	 */
	protected  function retrieveUsenetCrawler()
	{
		$data = $this->getUrl("http://www.usenet-crawler.com/predb?q=&c=&offset=0#results");
		if ($data === false) {
			return;
		}

		if (preg_match_all('/<tr class="(alt)?">\s*<td class="left">.*?<\/td>\s*<\/tr>/s', $data, $matches)) {
			foreach ($matches as $m) {
				foreach ($m as $match) {
					if (preg_match('/left">\s*(?P<date>.+?)\s*<\/td.+?left">\s*(.*?<u>)?(?P<title>.+?)(<\/.+>?<.+?>(?P<nuke>.+)?<\/.+>)?(<\/u><\/a>.+?)?\s*<\/td.+?href.+?">\s*(?P<category>.+?)\s*<\/a.+?mid">\s*((?P<size>\d+(\.\d+)?[KMGT]?B)(\/(?P<files>\d+F))?|--)\s*<\/td/s', $match, $matches2)) {
						$matches2['date'] = strtotime($matches2['date']);
						$matches2['source'] = 'usenet-crawler';
						$matches2['size'] = ((isset($matches2['size']) && !empty($matches2['size'])) ? $matches2['size'] : '');
						$matches2['files'] = ((isset($matches2['files']) && !empty($matches2['files'])) ? $matches2['files'] : '');
						if (isset($matches2['nuke']) && preg_match('/(\S+)\)/', $matches2['nuke'], $nuked)) {
							$matches2['nuked'] = PreDb::PRE_NUKED;
							$matches2['nukereason'] = $nuked[1];
						}
						$this->verifyPreData($matches2);
					}
				}
			}
		}
	}

	// Update a single release as it's created.
	public function matchPre($cleanerName)
	{
		if ($cleanerName == '') {
			return false;
		}
		$db = new DB();
		$x = $db->queryOneRow(sprintf('SELECT id FROM predb WHERE title = %s', $db->escapeString($cleanerName)));
		if (isset($x['id'])) {
			return array(
				"preid" => $x['id']
			);
		}
		//check if clean name matches a predb filename
		$y = $db->queryOneRow(sprintf('SELECT id, title FROM predb WHERE filename = %s', $db->escapeString($cleanerName)));
		if (isset($y['id'])) {
			return array(
				"title" => $y['title'],
				"preid" => $y['id']
			);
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

					if ($nfo->addAlternateNfo($buffer, $row, $nntp)) {
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

	// Matches the hashes within the predb table to release files and subjects (names) which are hashed.
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
			echo $this->c->header('Fixing search names' . $te . " using the predb hash.");
		}
		if ($db->dbSystem() === 'mysql') {
			$regex = "AND (r.ishashed = 1 OR rf.ishashed = 1)";
		} else if ($db->dbSystem() === 'pgsql') {
			$regex = "AND (r.ishashed = 1 OR rf.ishashed = 1)";
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
				if (preg_match('/[a-fA-F0-9]{32,40}/i', $row['name'], $matches)) {
					$updated = $updated + $namefixer->matchPredbHash($matches[0], $row, $echo, $namestatus, $this->echooutput, $show);
				} else if (preg_match('/[a-fA-F0-9]{32,40}/i', $row['filename'], $matches)) {
					$updated = $updated + $namefixer->matchPredbHash($matches[0], $row, $echo, $namestatus, $this->echooutput, $show);
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
