<?php

namespace nzedb\processing\post;

use \nzedb\db\Settings;

class AniDB
{
	const PROC_EXTFAIL = -1; // Release Anime title/episode # could not be extracted from searchname
	const PROC_NOMATCH = -2; // AniDB ID was not found in anidb table using extracted title/episode #

	const REGEX_NOFORN = 'English|Japanese|German|Danish|Flemish|Dutch|French|Swe(dish|sub)|Deutsch|Norwegian';

	/**
	 * @var bool Whether or not to echo messages to CLI
	 */
	public $echooutput;

	/**
	 * @var \nzedb\db\populate\AniDB
	 */
	public $padb;

	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var int number of AniDB releases to process
	 */
	private $aniqty;

	/**
	 * @var int The status of the release being processed
	 */
	private $status;

	/**
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());

		$qty = $this->pdo->getSetting('maxanidbprocessed');
		$this->aniqty = !empty($qty) ? $qty : 100;

		$this->status = 'NULL';
	}

	/**
	 * Queues anime releases for processing
	 */
	public function processAnimeReleases()
	{
		$results = $this->pdo->queryDirect(
						sprintf('
							SELECT searchname, id
							FROM releases
							WHERE nzbstatus = %d
							AND anidbid IS NULL
							AND categoryid = %d
							ORDER BY postdate DESC
							LIMIT %d',
							\NZB::NZB_ADDED,
							\Category::CAT_TV_ANIME,
							$this->aniqty
						)
		);

		if ($results instanceof \Traversable) {

			$this->padb = new \nzedb\db\populate\AniDB(['Echo' => $this->echooutput, 'Settings' => $this->pdo]);

			foreach ($results as $release) {
				$matched = $this->matchAnimeRelease($release);
				if ($matched === false) {
					$this->pdo->queryExec(
								sprintf('
									UPDATE releases
									SET anidbid = %d
									WHERE id = %d',
									$this->status,
									$release['id']
								)
					);
				}
			}

		} else {
			$this->pdo->log->doEcho($this->pdo->log->info("No work to process."), true);
		}
	}

	/**
	 * Selects episode info for a local match
	 *
	 * @param int $anidbId
	 * @param int $episode
	 * @return array|bool
	 */
	private function checkAniDBInfo($anidbId, $episode = -1)
	{
		return $this->pdo->queryOneRow(
						 sprintf('
							SELECT ae.anidbid, ae.episode_no,
								ae.airdate, ae.episode_title
							FROM anidb_episodes ae
							WHERE ae.anidbid = %d
							AND ae.episode_no = %d',
								 $anidbId,
								 $episode
						 )
		);
	}

	/**
	 * Sleeps between 10 and 15 seconds for AniDB API cooldown
	 */
	private function doRandomSleep()
	{
		sleep(rand(10, 15));
	}

	/**
	 * Extracts anime title and episode info from release searchname
	 *
	 * @param string $cleanName
	 * @return array
	 */
	private function extractTitleEpisode($cleanName = '')
	{
		if (preg_match('/(^|.*\")(\[[a-zA-Z\.\!?-]+\][\s_]*)?(\[BD\][\s_]*)?(\[\d{3,4}[ip]\][\s_]*)?(?P<title>[\w\s_.+!?\'-\(\)]+)(New Edit|(Blu-?ray)?( ?Box)?( ?Set)?)?([ _]-[ _]|([ ._-]Epi?(sode)?[ ._-]?0?)?[ ._-]?|[ ._-]Vol\.|[ ._-]E)(?P<epno>\d{1,3}|Movie|O[VA]{2}|Complete Series)(v\d|-\d+)?[-_. ].*[\[\(\"]/i', $cleanName, $matches)) {
			$matches['epno'] = (int) $matches['epno'];
			if (in_array($matches['epno'], ['Movie', 'OVA'])) {
				$matches['epno'] = (int) 1;
			}
		} else if (preg_match('/^(\[[a-zA-Z\.\-!?]+\][\s_]*)?(\[BD\])?(\[\d{3,4}[ip]\])?(?P<title>[\w\s_.+!?\'-\(\)]+)(New Edit|(Blu-?ray)?( ?Box)?( ?Set)?)?\s*[\(\[](BD|\d{3,4}[ipx])/i', $cleanName, $matches)) {
			$matches['epno'] = (int) 1;
		} else {
			if (nZEDb_DEBUG) {
				$this->pdo->log->doEcho(PHP_EOL . "Could not parse searchname {$cleanName}.", true);
			}
			$this->status = self::PROC_EXTFAIL;
		}

		if(!empty($matches['title'])) {
			$matches['title'] = trim(str_replace(['_', '.'], ' ', $matches['title']));
		}

		return $matches;
	}

	/**
	 * Retrieves AniDB Info using a cleaned name
	 *
	 * @param string $searchName
	 * @return array|bool
	 */
	private function getAnidbByName($searchName = '')
	{
		return $this->pdo->queryOneRow(
						sprintf("
							SELECT at.anidbid, at.title
							FROM anidb_titles AS at
							WHERE at.title %s",
							$this->pdo->likeString($searchName, true, true)
						)
		);
	}

	/**
	 * Matches the anime release to AniDB Info
	 * If no info is available locally the AniDB API is invoked
	 *
	 * @param array $release
	 * @return bool
	 */
	private function matchAnimeRelease($release = array())
	{
		$matched = false;
		$type    = 'Local';

		// clean up the release name to ensure we get a good chance at getting a valid title
		$cleanArr = $this->extractTitleEpisode($release['searchname']);

		if (is_array($cleanArr) && isset($cleanArr['title']) && is_numeric($cleanArr['epno'])) {

			echo $this->pdo->log->header(PHP_EOL . "Looking Up: ") .
				 $this->pdo->log->primary("   Title: {$cleanArr['title']}" . PHP_EOL .
										  "   Episode: {$cleanArr['epno']}");

			// get anidb number for the title of the name
			$anidbId = $this->getAnidbByName($cleanArr['title']);

			if ($anidbId === false) {
				$tmpName = preg_replace('/\s/', '%', $cleanArr['title']);
				$anidbId = $this->getAnidbByName($tmpName);
			}

			if (!empty($anidbId) && is_numeric($anidbId['anidbid']) && $anidbId['anidbid'] > 0) {

				$updatedAni = $this->checkAniDBInfo($anidbId['anidbid'], $cleanArr['epno']);

				if ($updatedAni === false) {
					if ($this->updateTimeCheck($anidbId['anidbid']) === false) {
						$this->padb->populateTable('info', $anidbId['anidbid']);
						$this->doRandomSleep();
						$updatedAni = $this->checkAniDBInfo($anidbId['anidbid']);
						$type       = 'Remote';
					} else {
						echo PHP_EOL .
							 $this->pdo->log->info("This AniDB ID was not found to be accurate locally, but has been updated too recently to check AniDB.") .
							 PHP_EOL;
					}
				}

				$this->updateRelease($anidbId['anidbid'],
									  $cleanArr['epno'],
									  $updatedAni['episode_title'],
									  $updatedAni['airdate'],
									  $release['id']);

				$this->pdo->log->doEcho(
							   $this->pdo->log->header("Matched {$type} AniDB ID: ") .
							   $this->pdo->log->alternateOver("   Title: ") .
							   $this->pdo->log->primary($anidbId['title']) .
							   $this->pdo->log->alternateOver("   Episode #: ") .
							   $this->pdo->log->primary($cleanArr['epno']) .
							   $this->pdo->log->alternateOver("   Episode Title: ") .
							   $this->pdo->log->primary($updatedAni['episode_title'])
				);

				$matched = true;
			} else {
				$this->status = self::PROC_NOMATCH;
			}
		}
		return $matched;
	}

	/**
	 * Updates releases based on matched Anime info
	 *
	 * @param int $anidbId
	 * @param int $epno
	 * @param string $title
	 * @param string $airdate
	 * @param int $relId
	 * @return array|bool
	 */
	private function updateRelease($anidbId, $epno, $title, $airdate, $relId)
	{

		$epno = 'E' . ($epno < 10 ? '0' : '') . $epno;

		$this->pdo->queryExec(
					sprintf("
						UPDATE releases
						SET anidbid = %d, seriesfull = %s, season = 'S01', episode = %s,
							tvtitle = %s, tvairdate = %s
						WHERE id = %d",
						$anidbId,
						$this->pdo->escapeString('S01' . $epno),
						$this->pdo->escapeString($epno),
						$this->pdo->escapeString($title),
						$this->pdo->escapeString($airdate),
						$relId
					)
		);
	}

	/**
	 * Checks a specific Anime title's last update time
	 *
	 * @param int $anidbId
	 * @return array|bool
	 */
	private function updateTimeCheck($anidbId)
	{
		return $this->pdo->queryOneRow(
						sprintf("
							SELECT anidbid
							FROM anidb_info ai
							WHERE DATEDIFF(NOW(), ai.updated) < 7
							AND ai.anidbid = %d",
							$anidbId
						)
		);
	}
}
