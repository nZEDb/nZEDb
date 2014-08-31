<?php

use nzedb\db\Settings;
use \nzedb\processing\PostProcess;

require_once nZEDb_LIBS . 'getid3/getid3/getid3.php';
require_once nZEDb_LIBS . 'rarinfo/par2info.php';
require_once nZEDb_LIBS . 'rarinfo/sfvinfo.php';

/**
 * Class Nfo
 * Class for handling fetching/storing of NFO files.
 */
class Nfo
{
	/**
	 * Instance of class DB
	 * @var nzedb\db\Settings
	 * @access private
	 */
	public $pdo;

	/**
	 * How many nfo's to process per run.
	 * @var int
	 * @access private
	 */
	private $nzbs;

	/**
	 * Max NFO size to process.
	 * @var string|int
	 * @access private
	 */
	private $maxsize;

	/**
	 * Max amount of times to retry to download a Nfo.
	 * @var string|int
	 * @access private
	 */
	private $maxRetries;

	/**
	 * Min NFO size to process.
	 * @var string|int
	 * @access private
	 */
	private $minsize;

	/**
	 * Path to temporarily store files.
	 * @var string
	 * @access private
	 */
	private $tmpPath;

	/**
	 * Echo to cli?
	 * @var bool
	 * @access protected
	 */
	protected $echo;

	const NFO_FAILED = -9; // We failed to get a NFO after admin set max retries.
	const NFO_UNPROC = -1; // Release has not been processed yet.
	const NFO_NONFO  =  0; // Release has no NFO.
	const NFO_FOUND  =  1; // Release has an NFO.

	/**
	 * Default constructor.
	 *
	 * @param array $options Class instance / echo to cli.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echo = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->nzbs = ($this->pdo->getSetting('maxnfoprocessed') != '') ? (int)$this->pdo->getSetting('maxnfoprocessed') : 100;
		$this->maxsize = ($this->pdo->getSetting('maxsizetoprocessnfo') != '') ? (int)$this->pdo->getSetting('maxsizetoprocessnfo') : 100;
		$this->maxsize = ($this->maxsize > 0 ? ('AND size < ' . ($this->maxsize * 1073741824)) : '');
		$this->minsize = ($this->pdo->getSetting('minsizetoprocessnfo') != '') ? (int)$this->pdo->getSetting('minsizetoprocessnfo') : 100;
		$this->minsize = ($this->minsize > 0 ? ('AND size > ' . ($this->minsize * 1048576)) : '');
		$this->maxRetries = (int)($this->pdo->getSetting('maxnforetries') >= 0 ? -((int)$this->pdo->getSetting('maxnforetries') + 1) : self::NFO_UNPROC);
		$this->maxRetries = ($this->maxRetries < -8 ? -8 : $this->maxRetries);
		$this->tmpPath = (string)$this->pdo->getSetting('tmpunrarpath');
		if (!preg_match('/[\/\\\\]$/', $this->tmpPath)) {
			$this->tmpPath .= DS;
		}
	}

	/**
	 * Look for a TvRage ID in a string.
	 *
	 * @param string  $str   The string with a TvRage ID.
	 * @return string The TVRage ID on success.
	 *
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
	 *
	 * @return bool               True on success, False on failure.
	 *
	 * @access public
	 */
	public function isNFO(&$possibleNFO, $guid) {
		if ($possibleNFO === false) {
			return false;
		}

		// Make sure it's not too big or small, size needs to be at least 12 bytes for header checking. Ignore common file types.
		$size = strlen($possibleNFO);
		if ($size < 65535 &&
			$size > 11 &&
			!preg_match(
				'/\A(\s*<\?xml|=newz\[NZB\]=|RIFF|\s*[RP]AR|.{0,10}(JFIF|matroska|ftyp|ID3))|;\s*Generated\s*by.*SF\w/i'
				, $possibleNFO))
		{
			// File/GetId3 work with files, so save to disk.
			$tmpPath = $this->tmpPath . $guid . '.nfo';
			file_put_contents($tmpPath, $possibleNFO);

			// Linux boxes have 'file' (so should Macs), Windows *can* have it too: see GNUWIN.txt in docs.
			if (nzedb\utility\Utility::hasCommand('file')) {
				exec('file -b "' . $tmpPath . '"', $result);
				if (is_array($result)) {
					if (count($result) > 1) {
						$result = implode(',', $result[0]);
					} else {
						$result = $result[0];
					}
				}

				// Check if it's text.
				if (preg_match('/(ASCII|ISO-8859|UTF-(8|16|32).*?)\s*text/', $result)) {
					@unlink($tmpPath);
					return true;

				// Or binary.
				} else if (preg_match('/^(JPE?G|Parity|PNG|RAR|XML|(7-)?[Zz]ip)/', $result) ||
					preg_match('/[\x00-\x08\x12-\x1F\x0B\x0E\x0F]/', $possibleNFO))
				{
					@unlink($tmpPath);
					return false;
				}
			}

			// If above checks couldn't  make a categorical identification, Use GetId3 to check if it's an image/video/rar/zip etc..
			$getid3 = new getid3();
			$check = $getid3->analyze($tmpPath);
			@unlink($tmpPath);
			if (isset($check['error'])) {

				// Check if it's a par2.
				$par2info = new Par2Info();
				$par2info->setData($possibleNFO);
				if ($par2info->error) {

					// Check if it's an SFV.
					$sfv = new SfvInfo();
					$sfv->setData($possibleNFO);
					if ($sfv->error) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Add an NFO from alternate sources. ex.: PreDB, rar, zip, etc...
	 *
	 * @param string $nfo     The nfo.
	 * @param array  $release The SQL row for this release.
	 * @param object $nntp    Instance of class NNTP.
	 *
	 * @return bool           True on success, False on failure.
	 *
	 * @access public
	 */
	public function addAlternateNfo(&$nfo, $release, $nntp)
	{
		if ($release['id'] > 0 && $this->isNFO($nfo, $release['guid'])) {

			$check = $this->pdo->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $release['id']));

			if ($check === false) {
				$this->pdo->queryInsert(
					sprintf('INSERT INTO releasenfo (nfo, releaseid) VALUES (compress(%s), %d)',
						$this->pdo->escapeString($nfo),
						$release['id']
					)
				);
			}

			$this->pdo->queryExec(sprintf('UPDATE releases SET nfostatus = %d WHERE id = %d', self::NFO_FOUND, $release['id']));

			if (!isset($release['completion'])) {
				$release['completion'] = 0;
			}

			if ($release['completion'] == 0) {
				$nzbContents = new NZBContents(
					[
						'Echo' => $this->echo,
						'NNTP' => $nntp,
						'Nfo'  => $this,
						'Settings'   => $this->pdo,
						'PostProcess'   => new PostProcess(['Echo' => $this->echo, 'Settings' => $this->pdo, 'Nfo' => $this])
					]
				);
				$nzbContents->parseNZB($release['guid'], $release['id'], $release['group_id']);
			}
			return true;
		}
		return false;
	}

	/**
	 * Get a string like this:
	 * "AND r.nzbstatus = 1 AND r.nfostatus BETWEEN -8 AND -1 AND r.size < 1073741824 AND r.size > 1048576"
	 * To use in a query.
	 *
	 * @param Settings $pdo
	 *
	 * @return string
	 * @access public
	 * @static
	 */
	static public function NfoQueryString(Settings &$pdo)
	{
		$maxSize = $pdo->getSetting('maxsizetoprocessnfo');
		$minSize = $pdo->getSetting('minsizetoprocessnfo');
		$maxRetries = (int)($pdo->getSetting('maxnforetries') >= 0 ? -((int)$pdo->getSetting('maxnforetries') + 1) : self::NFO_UNPROC);
		return (
			sprintf(
				'AND r.nzbstatus = %d AND r.nfostatus BETWEEN %d AND %d %s %s',
				NZB::NZB_ADDED,
				($maxRetries < -8 ? -8 : $maxRetries),
				self::NFO_UNPROC,
				(($maxSize != '' && $maxSize > 0) ? ('AND r.size < ' . ($maxSize * 1073741824)) : ''),
				(($minSize != '' && $minSize > 0) ? ('AND r.size > ' . ($minSize * 1048576)) : '')
			)
		);
	}

	/**
	 * Attempt to find NFO files inside the NZB's of releases.
	 *
	 * @param object $nntp           Instance of class NNTP.
	 * @param string $groupID        (optional) Group ID.
	 * @param string $guidChar       (optional) First character of the release GUID (used for multi-processing).
	 * @param int    $processImdb    (optional) Attempt to find IMDB id's in the NZB?
	 * @param int    $processTvrage  (optional) Attempt to find TvRage id's in the NZB?
	 *
	 * @return int                   How many NFO's were processed?
	 *
	 * @access public
	 */
	public function processNfoFiles($nntp, $groupID = '', $guidChar = '', $processImdb = 1, $processTvrage = 1)
	{
		$ret = 0;
		$guidCharQuery = ($guidChar === '' ? '' : 'AND r.guid ' . $this->pdo->likeString($guidChar, false, true));
		$groupIDQuery = ($groupID === '' ? '' : 'AND r.group_id = ' . $groupID);
		$optionsQuery = self::NfoQueryString($this->pdo);

		$res = $this->pdo->query(
			sprintf('
				SELECT r.id, r.guid, r.group_id, r.name
				FROM releases r
				WHERE 1=1 %s %s %s
				ORDER BY r.nfostatus ASC, r.postdate DESC
				LIMIT %d',
				$optionsQuery,
				$guidCharQuery,
				$groupIDQuery,
				$this->nzbs
			)
		);
		$nfoCount = count($res);

		if ($nfoCount > 0) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					PHP_EOL .
					($guidChar === '' ? '' : '[' . $guidChar . '] ') .
					($groupID === '' ? '' : '[' . $groupID . '] ') .
					'Processing ' . $nfoCount .
					' NFO(s), starting at ' . $this->nzbs .
					' * = hidden NFO, + = NFO, - = no NFO, f = download failed.'
				)
			);

			if ($this->echo) {
				// Get count of releases per nfo status
				$nfoStats = $this->pdo->queryDirect(
					sprintf('
						SELECT r.nfostatus AS status, COUNT(*) AS count
						FROM releases r
						WHERE 1=1 %s %s %s
						GROUP BY r.nfostatus
						ORDER BY r.nfostatus ASC',
						$optionsQuery,
						$guidCharQuery,
						$groupIDQuery
					)
				);
				if ($nfoStats instanceof Traversable) {
					$outString = PHP_EOL . 'Available to process';
					foreach ($nfoStats as $row) {
						$outString .= ', ' . $row['status'] . ' = ' . number_format($row['count']);
					}
					$this->pdo->log->doEcho($this->pdo->log->header($outString . '.'));
				}
			}

			$groups = new Groups(['Settings' => $this->pdo]);
			$nzbContents = new NZBContents(
				[
					'Echo' => $this->echo,
					'NNTP' => $nntp,
					'Nfo' => $this,
					'Settings' => $this->pdo,
					'PostProcess' => new PostProcess(['Echo' => $this->echo, 'Nfo' => $this, 'Settings' => $this->pdo])
				]
			);
			$movie = new Movie(['Echo' => $this->echo, 'Settings' => $this->pdo]);
			$tvRage = new TvRage(['Echo' => $this->echo, 'Settings' => $this->pdo]);

			foreach ($res as $arr) {
				$fetchedBinary = $nzbContents->getNFOfromNZB($arr['guid'], $arr['id'], $arr['group_id'], $groups->getByNameByID($arr['group_id']));
				if ($fetchedBinary !== false) {
					// Insert nfo into database.
					$cp = 'COMPRESS(%s)';
					$nc = $this->pdo->escapeString($fetchedBinary);

					$ckreleaseid = $this->pdo->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $arr['id']));
					if (!isset($ckreleaseid['id'])) {
						$this->pdo->queryInsert(sprintf('INSERT INTO releasenfo (nfo, releaseid) VALUES (' . $cp . ', %d)', $nc, $arr['id']));
					}
					$this->pdo->queryExec(sprintf('UPDATE releases SET nfostatus = %d WHERE id = %d', self::NFO_FOUND, $arr['id']));
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
		$releases = $this->pdo->queryDirect(
			sprintf(
				'SELECT r.id
				FROM releases r
				WHERE r.nzbstatus = %d
				AND r.nfostatus < %d %s %s',
				NZB::NZB_ADDED,
				$this->maxRetries,
				$groupIDQuery,
				$guidCharQuery
			)
		);

		if ($releases instanceof Traversable) {
			foreach ($releases as $release) {
				$this->pdo->queryExec(
					sprintf('DELETE FROM releasenfo WHERE nfo IS NULL AND releaseid = %d', $release['id'])
				);
			}
		}

		// Set releases with no NFO.
		$this->pdo->queryExec(
			sprintf('
				UPDATE releases r
				SET r.nfostatus = %d
				WHERE r.nzbstatus = %d
				AND r.nfostatus < %d %s %s',
				self::NFO_FAILED,
				NZB::NZB_ADDED,
				$this->maxRetries,
				$groupIDQuery,
				$guidCharQuery
			)
		);

		if ($this->echo) {
			if ($nfoCount > 0) {
				echo PHP_EOL;
			}
			if ($ret > 0) {
				$this->pdo->log->doEcho($ret . ' NFO file(s) found/processed.', true);
			}
		}
		return $ret;
	}

}
