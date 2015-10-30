<?php
namespace nzedb\processing\tv;

use libs\Moinax\TVDB\Client;
use nzedb\ReleaseImage;

/**
 * Class TVDB -- functions used to post process releases against TVDB
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
	 * @var string URL for show fanart
	 */
	public $fanartUrl;

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
		$this->fanartUrl = self::TVDB_URL . DS . 'banners/_cache/fanart/original/%s-1.jpg';

		$this->serverTime = $this->client->getServerTime();
		$this->timeZone = new \DateTimeZone('UTC');
		$this->timeFormat = 'Y-m-d H:i:s';
	}

	/**
	 * Main processing director function for TVDB
	 * Calls work query function and initiates processing
	 *
	 * @param            $groupID
	 * @param            $guidChar
	 * @param            $processTV
	 * @param bool|false $local
	 */
	public function processTVDB($groupID, $guidChar, $processTV, $local = false)
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
							echo $this->pdo->log->primaryOver("Video ID for ") .
								$this->pdo->log->headerOver($release['cleanname']) .
								$this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TVDB
						$tvdbShow = $this->getShowInfo((string)$release['cleanname']);

						if (is_array($tvdbShow)) {
							$tvdbShow['country'] = (isset($release['country']) && $release['country'] !== 2
								? (string)$release['country']
								: ''
							);
							$videoId = $this->add($tvdbShow);
							$tvdbid = (int)$tvdbShow['tvdbid'];
						}
					} else if ($this->echooutput) {
						echo $this->pdo->log->primaryOver("Video ID for ") .
							$this->pdo->log->headerOver($release['cleanname']) .
							$this->pdo->log->primary(" found in local db, attempting episode match.");
					}

					if (is_numeric($videoId) && $videoId > 0 && is_numeric($tvdbid) && $tvdbid > 0) {
						// Now that we have valid video and tvdb ids, try to get the poster
						$this->getPoster($videoId, $tvdbid);

						$seasonNo = preg_replace('/^S0*/i', '', $release['season']);
						$episodeNo = preg_replace('/^E0*/i', '', $release['episode']);

						if ($episodeNo === 'all') {
							// Set the video ID and leave episode 0
							$this->setVideoIdFound($videoId, $row['id'], 0);
							echo $this->pdo->log->primary("Found TVDB Match for Full Season!");
							continue;
						}

						// Download all episodes if new show to reduce API/bandwidth usage
						if ($this->countEpsByVideoID($videoId) === false) {
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
								$episode = $this->addEpisode($videoId, $tvdbEpisode);
							}
						}

						if ($episode !== false && is_numeric($episode) && $episode > 0) {
							// Mark the releases video and episode IDs
							$this->setVideoIdFound($videoId, $row['id'], $episode);
							if ($this->echooutput) {
								echo $this->pdo->log->primary("Found TVDB Match!");
							}
							continue;
						}
					}
				} //Processing failed, set the episode ID to the next processing group
				$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
			}
		}
	}

	/**
	 * Placeholder for Videos getBanner
	 *
	 * @param $videoID
	 * @param $siteId
	 *
	 * @return bool
	 */
	protected function getBanner($videoID, $siteId)
	{
		return false;
	}

	/**
	 * Calls the API to perform initial show name match to TVDB title
	 * Returns a formatted array of show data or false if no match
	 *
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
		} catch (\Exception $error) {
		}

		sleep(1);

		if (is_array($response)) {
			foreach ($response as $show) {
				if ($this->checkRequired($show, 'tvdbS')) {
					// Check for exact title match first and then terminate if found
					if ($show->name === $cleanName) {
						$highest = $show;
						break;
					}

					// Check each show title for similarity and then find the highest similar value
					$matchPercent = $this->checkMatch($show->name, $cleanName, self::MATCH_PROBABILITY);

					// If new match has a higher percentage, set as new matched title
					if ($matchPercent > $highestMatch) {
						$highestMatch = $matchPercent;
						$highest = $show;
					}

					// Check for show aliases and try match those too
					if (is_array($show->aliasNames) && !empty($show->aliasNames)) {
						foreach ($show->aliasNames as $key => $name) {
							$matchPercent = $this->CheckMatch($name, $cleanName, $matchPercent);
							if ($matchPercent > $highestMatch) {
								$highestMatch = $matchPercent;
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
	 * Retrieves the poster art for the processed show
	 *
	 * @param int $videoId -- the local Video ID
	 * @param int $showId  -- the TVDB ID
	 *
	 * @return null
	 */
	protected function getPoster($videoId, $showId)
	{
		$ri = new ReleaseImage($this->pdo);

		// Try to get the Poster
		$hascover = $ri->saveImage($videoId, sprintf($this->posterUrl, $showId), $this->imgSavePath, '', '');

		// Couldn't get poster, try fan art instead
		if ($hascover !== 1) {
			$hascover = $ri->saveImage($videoId, sprintf($this->fanartUrl, $showId), $this->imgSavePath, '', '');
		}
		// Mark it retrieved if we saved an image
		if ($hascover == 1) {
			$this->setCoverFound($videoId);
		}
	}

	/**
	 * Gets the specific episode info for the parsed release after match
	 * Returns a formatted array of episode data or false if no match
	 *
	 * @param integer $tvdbid
	 * @param integer $season
	 * @param integer $episode
	 * @param string  $airdate
	 * @param integer $videoId
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
			if ($this->checkRequired($response, 'tvdbE')) {
				$return = $this->formatEpisodeArr($response);
			}
		} else if (is_array($response) && isset($response['episodes']) && $videoId > 0) {
			foreach ($response['episodes'] as $singleEpisode) {
				if ($this->checkRequired($singleEpisode, 'tvdbE')) {
					$this->addEpisode($videoId, $this->formatEpisodeArr($singleEpisode));
				}
			}
		}

		return $return;
	}

	/**
	 * Assigns API show response values to a formatted array for insertion
	 * Returns the formatted array
	 *
	 * @param $show
	 *
	 * @return array
	 */
	private function formatShowArr($show)
	{
		$show->firstAired->setTimezone($this->timeZone);
		preg_match('/tt(?P<imdbid>\d{6,7})$/i', $show->imdbId, $imdb);

		return [
			'title'     => (string)$show->name,
			'summary'   => (string)$show->overview,
			'started'   => (string)$show->firstAired->format($this->timeFormat),
			'publisher' => (string)$show->network,
			'source'    => (int)parent::SOURCE_TVDB,
			'imdb'    => (int)(isset($imdb['imdbid']) ? $imdb['imdbid'] : 0),
			'tvdb'    => (int)$show->id,
			'trakt'   => 0,
			'tvrage'  => 0,
			'tvmaze'  => 0,
			'tmdb'    => 0
		];
	}

	/**
	 * Assigns API episode response values to a formatted array for insertion
	 * Returns the formatted array
	 *
	 * @param $episode
	 *
	 * @return array
	 */
	private function formatEpisodeArr($episode)
	{
		$episode->firstAired->setTimezone($this->timeZone);

		return [
			'title'       => (string)$episode->name,
			'series'      => (int)$episode->season,
			'episode'     => (int)$episode->number,
			'se_complete' => (string)'S' . sprintf('%02d', $episode->season) . 'E' . sprintf('%02d', $episode->number),
			'firstaired'  => (string)$episode->firstAired->format($this->timeFormat),
			'summary'     => (string)$episode->overview
		];
	}
}
