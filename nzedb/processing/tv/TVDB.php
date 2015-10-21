<?php
namespace nzedb\processing\tv;

use libs\Moinax\TVDB\Client;
use nzedb\ReleaseImage;

/**
 * Class TVDB
 */
class TVDB extends TV
{
	const TVDB_URL = 'http://thetvdb.com';
	const TVDB_API_KEY = '5296B37AEC35913D';
	const MATCH_PROBABILITY = 75;

	/**
	 * @var \libs\Moinax\TVDB\Client
	 */
	public $client;

	/**
	 * @string URL for show poster art
	 */
	public $posterUrl;

	/**
	 * @string Path to Save Images
	 */
	public $imgSavePath;

	/**
	 * @string The Timestamp of the TVDB Server
	 */
	private $serverTime;

	/**
	 * @string DateTimeZone Object - UTC
	 */
	private $timeZone;

	/**
	 * @string MySQL DATETIME Format
	 */
	private $timeFormat;

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new Client(self::TVDB_URL, self::TVDB_API_KEY);
		$this->posterUrl = self::TVDB_URL . DS . 'banners/_cache/posters/%s-1.jpg';
		$this->imgSavePath = nZEDb_COVERS . 'tvshows' . DS;

		$this->serverTime = $this->client->getServerTime();
		$this->timeZone = new \DateTimeZone('UTC');
		$this->timeFormat = 'Y-m-d H:i:s';
	}

	/**
	 * @param            $groupID
	 * @param            $guidChar
	 * @param            $processTV
	 * @param bool|false $local
	 */
	public function processTVDB ($groupID, $guidChar, $processTV, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $processTV, parent::PROCESS_TVDB);

		$tvcount = $res->rowCount();

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TVDB lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $row) {

				$tvdbid = false;

				// Clean the show name for better match probability
				$release = $this->parseNameEpSeason($row['searchname']);
				if (is_array($release) && $release['name'] != '') {

					// Find the Video ID if it already exists by checking the title.
					$videoId = $this->getByTitle($release['cleanname']);

					if ($videoId !== false) {
						$tvdbid = $this->getSiteByID('tvdb', $videoId);
					}

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					} else {
						$lookupSetting = true;
					}

					if ($tvdbid === false && $lookupSetting) {

						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo	$this->pdo->log->primaryOver("Video ID for ") .
									$this->pdo->log->headerOver($release['cleanname']) .
									$this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TVDB
						$tvdbShow = $this->getShowInfo((string)$release['cleanname']);

						if (is_array($tvdbShow)) {
							$tvdbShow['country']  = (isset($release['country']) && strlen($release['country']) == 2
												? (string)$release['country']
												: ''
							);

							$videoId = $this->add(
										$tvdbShow['column'],
										$tvdbShow['siteid'],
										$tvdbShow['title'],
										$tvdbShow['summary'],
										$tvdbShow['country'],
										$tvdbShow['started'],
										$tvdbShow['publisher'],
										$tvdbShow['source'],
										$tvdbShow['imdbid']
							);
							$tvdbid = (int)$tvdbShow['siteid'];
						}
					} else if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($release['cleanname']) .
								 $this->pdo->log->primary(" found in local db, attempting episode match.");
					}

					if (is_numeric($videoId) && $videoId > 0 && is_numeric($tvdbid) && $tvdbid > 0) {
						// Now that we have valid video and tvdb ids, try to get the poster
						$this->getPoster($videoId, $tvdbid);

						$seasonNo = preg_replace('/^S0*/', '', $release['season']);
						$episodeNo = preg_replace('/^E0*/', '', $release['episode']);

						// Download all episodes if new show to reduce API/bandwidth usage
						if ($this->checkIfNoEpisodes($videoId) === false) {
							$this->getEpisodeInfo($tvdbid, -1, -1, '', $videoId);
						}

						// Check if we have the episode for this video ID
						$episode = $this->getBySeasonEp($videoId, $seasonNo, $episodeNo, $release['airdate']);


						if ($episode === false && $lookupSetting) {
							// Send the request for the episode to TVDB
							$tvdbEpisode = $this->getEpisodeInfo(
										$tvdbid,
										$seasonNo,
										$episodeNo,
										$release['airdate']
							);

							if ($tvdbEpisode) {
								$episode = $this->addEpisode(
												$videoId,
												$tvdbEpisode['season'],
												$tvdbEpisode['episode'],
												$tvdbEpisode['se_complete'],
												$tvdbEpisode['title'],
												$tvdbEpisode['firstaired'],
												$tvdbEpisode['summary']
								);
							}
						}

						if ($episode !== false && is_numeric($episode) && $episode > 0) {
							// Mark the releases video and episode IDs
							$this->setVideoIdFound($videoId, $row['id'], $episode);
							if ($this->echooutput) {
								echo	$this->pdo->log->primary("Found TVDB Match!");
							}
							continue;
						}
					}
				} //Processing failed, set the episode ID to the next processing group
				$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
			}
		}
	}

	protected function getBanner($videoID, $siteId)
	{
		return false;
	}

	/**
	 * @param $cleanName
	 *
	 * @return array|bool
	 */
	protected function getShowInfo($cleanName)
	{
		$return = $response = false;
		$highestMatch = 0;
		try {
			$response = (array)$this->client->getSeries($cleanName, 'en');
		} catch (\Exception $error) { }

		sleep(1);

		if (is_array($response)) {
			foreach ($response as $show) {
				if ($this->checkRequired($show, 1)) {
					// Check for exact title match first and then terminate if found
					if ($show->name === $cleanName) {
						$highest = $show;
						break;
					}

					// Check each show title for similarity and then find the highest similar value
					similar_text($show->name, $cleanName, $matchProb);

					if (nZEDb_DEBUG) {
						echo PHP_EOL . sprintf('Match Percentage: %d percent between %s and %s', $matchProb, $show->name, $cleanName) . PHP_EOL;
					}

					if ($matchProb >= self::MATCH_PROBABILITY && $matchProb > $highestMatch) {
						$highestMatch = $matchProb;
						$highest = $show;
					}
					if (is_array($show->aliasNames) && !empty($show->aliasNames)) {
						foreach ($show->aliasNames as $key => $name) {
							similar_text($name, $cleanName, $matchProb);
							if ($matchProb >= self::MATCH_PROBABILITY && $matchProb > $highestMatch) {
								$highestMatch = $matchProb;
								$highest = $show;
							}
						}
					}
				}
			}
			if (isset($highest)) {
				$return = $this->formatShowArr($highest);
			}
		}
		return $return;
	}

	/**
	 * @param $videoId
	 * @param $showId
	 */
	protected function getPoster($videoId, $showId)
	{
		$hascover = (new ReleaseImage($this->pdo))->saveImage(
							$videoId,
							sprintf($this->posterUrl, $showId),
							$this->imgSavePath,
							'',
							''
		);
		if ($hascover == 1) {
			$this->setCoverFound($videoId);
		}
	}

	/**
	 * @param        $tvdbid
	 * @param        $season
	 * @param        $episode
	 *
	 * @param string $airdate
	 *
	 * @param int    $videoId
	 *
	 * @return array|bool
	 */
	protected function getEpisodeInfo($tvdbid, $season, $episode, $airdate = '', $videoId = 0)
	{
		$return = $response = false;

		if ($airdate !== '') {
			try {
				$response = $this->client->getEpisodeByAirDate($tvdbid, $airdate);
			} catch (\Exception $error) {
			}
		} else if ($videoId > 0) {
			try {
				$response = $this->client->getSerieEpisodes($tvdbid, 'en');
			} catch (\Exception $error) {
			}
		} else {
			try {
				$response = $this->client->getEpisode($tvdbid, $season, $episode);
			} catch (\Exception $error) {
			}
		}

		sleep(1);

		if (is_object($response)) {
			if ($this->checkRequired($response, 2)) {
				$return = $this->formatEpisodeArr($response);
			}
		} else if (is_array($response) && isset($response['episodes']) && $videoId > 0) {
			foreach($response['episodes'] as $singleEpisode) {
				if ($this->checkRequired($singleEpisode, 2)) {
					$newEpisode = $this->formatEpisodeArr($singleEpisode);
					$this->addEpisode(
						$videoId,
						$newEpisode['season'],
						$newEpisode['episode'],
						$newEpisode['se_complete'],
						$newEpisode['title'],
						$newEpisode['firstaired'],
						$newEpisode['summary']
					);
				}
			}
		}
		return $return;
	}

	/**
	 * @param $show
	 *
	 * @return array
	 */
	private function formatShowArr($show)
	{
		$show->firstAired->setTimezone($this->timeZone);
		preg_match('/tt(?P<imdbid>\d{7})$/i', $show->imdbId, $imdb);

		return	[
					'column'    => (string)'tvdb',
					'siteid'    => (int)$show->id,
					'title'     => (string)$show->name,
					'summary'   => (string)$show->overview,
					'started'   => (string)$show->firstAired->format($this->timeFormat),
					'publisher' => (string)$show->network,
					'source'    => (int)parent::SOURCE_TVDB,
					'imdbid'    => (int)(isset($imdb['imdbid']) ? $imdb['imdbid'] : 0)
				];
	}

	/**
	 * @param $episode
	 *
	 * @return array
	 */
	private function formatEpisodeArr($episode)
	{
		$episode->firstAired->setTimezone($this->timeZone);

		return [
			'title'       => (string)$episode->name,
			'season'      => (int)$episode->season,
			'episode'     => (int)$episode->number,
			'se_complete' => (string)'S' . sprintf('%02d', $episode->season) . 'E' . sprintf('%02d', $episode->number),
			'firstaired'  => (string)$episode->firstAired->format($this->timeFormat),
			'summary'     => (string)$episode->overview
		];
	}

	/**
	 * @param $array
	 * @param $type
	 *
	 * @return bool
	 */
	private function checkRequired($array, $type)
	{
		$required = false;

		switch ($type) {
			case 1:
				$required = ['id', 'name', 'overview', 'firstAired'];
				break;
			case 2:
				$required = ['name', 'season', 'number', 'firstAired', 'overview'];
				break;
		}

		if (is_array($required)) {
			foreach ($required as $req) {
				if (!isset($array->$req)) {
					return false;
				}
			}
		}
		return true;
	}
}
