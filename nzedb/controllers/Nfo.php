<?php

use nzedb\db\DB;

/**
 * Class Nfo
 * Class for handling fetching/storing of NFO files.
 * @public
 */
class Nfo
{
	/**
	 * Site settings.
	 *
	 * @var bool|stdClass
	 * @access private
	 */
	private $site;

	/**
	 * How many nfo's to process per run.
	 *
	 * @var int
	 * @access private
	 */
	private $nzbs;

	/**
	 * Max NFO size to process.
	 *
	 * @var int
	 * @access private
	 */
	private $maxsize;

	/**
	 * Path to temporarily store files.
	 *
	 * @var string
	 * @access private
	 */
	private $tmpPath;

	/**
	 * Instance of class ColorCLI
	 *
	 * @var ColorCLI
	 * @access private
	 */
	private $c;

	/**
	 * Instance of class DB
	 *
	 * @var DB
	 * @access private
	 */
	private $db;

	/**
	 * Primary color for console text output.
	 *
	 * @var string
	 * @access private
	 */
	private $primary = 'Green';

	/**
	 * Color for warnings on console text output.
	 *
	 * @var string
	 * @access private
	 */
	private $warning = 'Red';

	/**
	 * Color for headers(?) on console text output.
	 *
	 * @var string
	 * @access private
	 */
	private $header = 'Yellow';

	/**
	 * Echo to cli?
	 *
	 * @var bool
	 */
	protected $echo;

	/**
	 * Default constructor.
	 *
	 * @param bool $echo Echo to cli.
	 *
	 * @access public
	 */
	public function __construct($echo = false) {
		$this->echo = ($echo && nZEDb_ECHOCLI);
		$s = new Sites();
		$this->c = new ColorCLI();
		$this->db = new DB();
		$this->site = $s->get();
		$this->nzbs = (!empty($this->site->maxnfoprocessed)) ? $this->site->maxnfoprocessed : 100;
		$this->maxsize = (!empty($this->site->maxsizetopostprocess)) ? $this->site->maxsizetopostprocess : 100;
		$this->tmpPath = $this->site->tmpunrarpath;
		if (substr($this->tmpPath, -1) !== DIRECTORY_SEPARATOR) {
			$this->tmpPath = $this->tmpPath . DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Look for a TvRage ID in a string.
	 *
	 * @param string  $str   The string with a TvRage ID.
	 * @return string The TVRage ID on success.
	 * @return bool   False on failure.
	 *
	 * @access public
	 */
	public function parseRageId($str) {
		if (preg_match('/tvrage\.com\/shows\/id-(\d{1,6})/i', $str, $matches)) {
			return trim($matches[1]);
		}
		return false;
	}

	/**
	 * Confirm this is an NFO file.
	 *
	 * @param string $possibleNFO The nfo.
	 * @param string $guid        The guid of the release.
	 * @return bool               True on success, False on failure.
	 *
	 * @access public
	 */
	public function isNFO($possibleNFO, $guid) {
		$r = false;
		if ($possibleNFO === false) {
			return $r;
		}

		// Make sure it's not too big or small, size needs to be at least 12 bytes for header checking.
		$size = strlen($possibleNFO);
		if ($size < 100 * 1024 && $size > 12) {
			// Ignore common file types.
			if (preg_match(
				'/(^RIFF|)<\?xml|;\s*Generated\s*by.*SF\w|\A\s*[RP]AR|\A.{0,10}(JFIF|matroska|ftyp|ID3)|\A=newz\[NZB\]=/i'
				, $possibleNFO)) {
				return $r;
			}

			// file/getid3 work with files, so save to disk
			$tmpPath = $this->tmpPath.$guid.'.nfo';
			file_put_contents($tmpPath, $possibleNFO);

			// Linux boxes have 'file' (so should Macs)
			if (strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
				exec("file -b $tmpPath", $result);
				if (is_array($result)) {
					if (count($result) > 1) {
						$result = implode(',', $result[0]);
					} else {
						$result = $result[0];
					}
				}
				$test = preg_match('#^.*(ISO-8859|UTF-(?:8|16|32) Unicode(?: \(with BOM\)|)|ASCII)(?: English| C++ Program|) text.*$#i', $result);
				// if the result is false, something went wrong, continue with getID3 tests.
				if ($test !== false) {
					if ($test == 1) {
						@unlink($tmpPath);
						return true;
					}

					// non-printable characters should never appear in text, so rule them out.
					$test = preg_match('#\x00|\x01|\x02|\x03|\x04|\x05|\x06|\x07|\x08|\x0B|\x0E|\x0F|\x12|\x13|\x14|\x15|\x16|\x17|\x18|\x19|\x1A|\x1B|\x1C|\x1D|\x1E|\x1F#', $possibleNFO);
					if ($test) {
						@unlink($tmpPath);
						return false;
					}
				}
			}

			// If on Windows, or above checks couldn't  make a categorical identification,
			// Use getid3 to check if it's an image/video/rar/zip etc..
			require_once nZEDb_LIBS . 'getid3/getid3/getid3.php';
			$getid3 = new getid3();
			$check = $getid3->analyze($tmpPath);
			unset($getid3);
			@unlink($tmpPath);
			unset($tmpPath);
			if (isset($check['error'])) {
				// Check if it's a par2.
				require_once nZEDb_LIBS . 'rarinfo/par2info.php';
				$par2info = new Par2Info();
				$par2info->setData($possibleNFO);
				if ($par2info->error) {
					// Check if it's an SFV.
					require_once nZEDb_LIBS . 'rarinfo/sfvinfo.php';
					$sfv = new SfvInfo();
					$sfv->setData($possibleNFO);
					if ($sfv->error) {
						return true;
					}
				}
			}
		}
		return $r;
	}

	/**
	 * Add an NFO from alternate sources. ex.: PreDB, rar, zip, etc...
	 *
	 * @param object $db      Instance of class DB.
	 * @param string $nfo     The nfo.
	 * @param array  $release The SQL row for this release.
	 * @param object $nntp    Instance of class NNTP.
	 *
	 * @return bool           True on success, False on failure.
	 *
	 * @access public
	 */
	public function addAlternateNfo($db, $nfo, $release, $nntp) {
		if (!isset($nntp)) {
			exit($this->c->error("NFO->addAlternateNfo() Not connected to usenet.\n"));
		}

		if ($release['id'] > 0) {
			if ($db->dbSystem() === 'mysql') {
				$compress = 'compress(%s)';
				$nc = $db->escapeString($nfo);
			} else {
				$compress = '%s';
				$nc = $db->escapeString(utf8_encode($nfo));
			}
			$ckreleaseid = $db->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $release['id']));
			if (!isset($ckreleaseid['id'])) {
				$db->queryInsert(sprintf('INSERT INTO releasenfo (nfo, releaseid) VALUES (' . $compress . ', %d)', $nc, $release['id']));
			}
			$db->queryExec(sprintf('UPDATE releases SET nfostatus = 1 WHERE id = %d', $release['id']));
			if (!isset($release['completion'])) {
				$release['completion'] = 0;
			}
			if ($release['completion'] == 0) {
				$nzbContents = new NZBContents(array('echo' => $this->echo, 'nntp' => $nntp, 'nfo' => $this, 'db' => $db, 'pp' => new PostProcess(true)));
				$nzbContents->parseNZB($release['guid'], $release['id'], $release['groupid']);
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Attempt to find NFO files inside the NZB's of releases.
	 *
	 * @param string $releaseToWork
	 * @param int $processImdb       Attempt to find IMDB id's in the NZB?
	 * @param int $processTvrage     Attempt to find TvRage id's in the NZB?
	 * @param string $groupID        (optional) The group ID to work on.
	 * @param object $nntp           Instance of class NNTP.
	 *
	 * @return int                   How many NFO's were processed?
	 *
	 * @access public
	 */
	public function processNfoFiles($releaseToWork = '', $processImdb = 1, $processTvrage = 1, $groupID = '', $nntp) {
		if (!isset($nntp)) {
			exit($this->c->error("Unable to connect to usenet.\n"));
		}

		$nfoCount = $ret = 0;
		$groupID = $groupID === '' ? '' : 'AND groupid = ' . $groupID;
		$res = array();

		if ($releaseToWork === '') {
			$i = -1;
			while (($nfoCount != $this->nzbs) && ($i >= -6)) {
				$res = $this->db->query(sprintf('SELECT id, guid, groupid, name FROM releases WHERE nzbstatus = 1 AND nfostatus between %d AND -1 AND size < %s ' . $groupID . ' LIMIT %d', $i, $this->maxsize * 1073741824, $this->nzbs));
				$nfoCount = count($res);
				$i--;
			}
		} else {
			$pieces = explode('           =+=            ', $releaseToWork);
			$res = array(array('id' => $pieces[0], 'guid' => $pieces[1], 'groupid' => $pieces[2], 'name' => $pieces[3]));
			$nfoCount = 1;
		}

		if ($nfoCount > 0) {
			if ($releaseToWork === '') {
				$this->c->doEcho($this->c->primary(
					'Processing ' . $nfoCount .
					' NFO(s), starting at ' . $this->nzbs .
					' * = hidden NFO, + = NFO, - = no NFO, f = download failed.'
				));

				// Get count of releases per nfo status
				$outString = 'Available to process';
				for ($i = -1; $i >= -6; $i--) {
					$ns =  $this->db->query('SELECT COUNT(*) AS count FROM releases WHERE nfostatus = ' . $i);
					$outString .= ', ' . $i . ' = ' . number_format($ns[0]['count']);
				}
				$this->c->doEcho($this->c->header($outString . '.'));
			}
			$groups = new Groups();
			$nzbContents = new NZBContents(array('echo' => $this->echo, 'nntp' => $nntp, 'nfo' => $this, 'db' => $this->db, 'pp' => new PostProcess(true)));
			$movie = new Movie($this->echo);
			$tvRage = new TvRage($this->echo);

			foreach ($res as $arr) {
				$fetchedBinary = $nzbContents->getNFOfromNZB($arr['guid'], $arr['id'], $arr['groupid'], $groups->getByNameByID($arr['groupid']));
				if ($fetchedBinary !== false) {
					// Insert nfo into database.
					$cp = $nc = null;
					if ($this->db->dbSystem() === 'mysql') {
						$cp = 'COMPRESS(%s)';
						$nc = $this->db->escapeString($fetchedBinary);
					} else if ($this->db->dbSystem() === 'pgsql') {
						$cp = '%s';
						$nc = $this->db->escapeString(utf8_encode($fetchedBinary));
					}
					$ckreleaseid = $this->db->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $arr['id']));
					if (!isset($ckreleaseid['id'])) {
						$this->db->queryInsert(sprintf('INSERT INTO releasenfo (nfo, releaseid) VALUES (' . $cp . ', %d)', $nc, $arr['id']));
					}
					$this->db->queryExec(sprintf('UPDATE releases SET nfostatus = 1 WHERE id = %d', $arr['id']));
					$ret++;
					$movie->doMovieUpdate($fetchedBinary, 'nfo', $arr['id'], $processImdb);

					// If set scan for tvrage info.
					if ($processTvrage == 1) {
						$rageId = $this->parseRageId($fetchedBinary);
						if ($rageId !== false) {
							$show = $tvRage->parseNameEpSeason($arr['name']);
							if (is_array($show) && $show['name'] != '') {
								// Update release with season, ep, and air date info (if available) from release title.
								$tvRage->updateEpInfo($show, $arr['id']);

								$rid = $tvRage->getByRageID($rageId);
								if (!$rid) {
									$tvrShow = $tvRage->getRageInfoFromService($rageId);
									$tvRage->updateRageInfo($rageId, $show, $tvrShow, $arr['id']);
								}
							}
						}
					}
				}
			}
		}

		// Remove nfo that we cant fetch after 5 attempts.
		if ($releaseToWork === '') {
			$relres = $this->db->query('SELECT id FROM releases WHERE nzbstatus = 1 AND nfostatus < -6');
			foreach ($relres as $relrow) {
				$this->db->queryExec(sprintf('DELETE FROM releasenfo WHERE nfo IS NULL and releaseid = %d', $relrow['id']));
			}

			if ($this->echo) {
				if ($nfoCount > 0 && $releaseToWork === '') {
					echo "\n";
				}
				if ($ret > 0 && $releaseToWork === '') {
					$this->c->doEcho($ret . ' NFO file(s) found/processed.', true);
				}
			}
		}
		return $ret;
	}

}
