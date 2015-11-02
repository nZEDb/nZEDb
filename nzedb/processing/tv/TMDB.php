<?php
namespace nzedb\processing\tv;

use nzedb\libraries\TmdbAPI;
use nzedb\ReleaseImage;

class TMDB extends TV
{
	const MATCH_PROBABILITY = 75;

	/**
	 * @var string The URL for the medium sized image for poster
	 */
	private $posterUrl;

	/**
	 * Construct. Instanciate TMDB Class
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new TmdbAPI($this->pdo->getSetting('tmdbkey'));
	}

	/**
	 * Fetch banner from site.
	 *
	 * @param $videoId
	 * @param $siteID
	 *
	 * @return bool
	 */
	public function getBanner($videoId, $siteID)
	{
		return false;
	}

	/**
	 * Main processing director function for TMDB
	 * Calls work query function and initiates processing
	 *
	 * @param            $groupID
	 * @param            $guidChar
	 * @param            $processTV
	 * @param bool|false $local
	 */
	public function processTMDB ($groupID, $guidChar, $processTV, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $processTV, parent::PROCESS_TMDB);

		$tvcount = $res->rowCount();

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TMDB lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $row) {

				$tmdbid = false;
				$this->posterUrl = '';

				// Clean the show name for better match probability
				$release = $this->parseNameEpSeason($row['searchname']);
				if (is_array($release) && $release['name'] != '') {

					// Find the Video ID if it already exists by checking the title.
					$videoId = $this->getByTitle($release['cleanname'], parent::TYPE_TV);

					if ($videoId !== false) {
						$tmdbid = $this->getSiteByID('tmdb', $videoId);
					}

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					} else {
						$lookupSetting = true;
					}

					if ($tmdbid === false && $lookupSetting) {

						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo	$this->pdo->log->primaryOver("Video ID for ") .
								$this->pdo->log->headerOver($release['cleanname']) .
								$this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TMDB
						$tmdbShow = $this->getShowInfo((string)$release['cleanname']);

						if (is_array($tmdbShow)) {
							$videoId = $this->add($tmdbShow);
							$tmdbid = (int)$tmdbShow['tmdbid'];
						}
					} else if ($this->echooutput) {
						echo $this->pdo->log->primaryOver("Video ID for ") .
							$this->pdo->log->headerOver($release['cleanname']) .
							$this->pdo->log->primary(" found in local db, attempting episode match.");
					}

					if (is_numeric($videoId) && $videoId > 0 && is_numeric($tmdbid) && $tmdbid > 0) {
						// Now that we have valid video and tmdb ids, try to get the poster
						$this->getPoster($videoId, $tmdbid);

						$seasonNo = preg_replace('/^S0*/i', '', $release['season']);
						$episodeNo = preg_replace('/^E0*/i', '', $release['episode']);

						if ($episodeNo === 'all') {
							// Set the video ID and leave episode 0
							$this->setVideoIdFound($videoId, $row['id'], 0);
							echo $this->pdo->log->primary("Found TMDB Match for Full Season!");
							continue;
						}

						// Download all episodes if new show to reduce API/bandwidth usage
						if ($this->countEpsByVideoID($videoId) === false) {
							$this->getEpisodeInfo($tmdbid, -1, -1, '', $videoId);
						}

						// Check if we have the episode for this video ID
						$episode = $this->getBySeasonEp($videoId, $seasonNo, $episodeNo, $release['airdate']);

						if ($episode === false && $lookupSetting) {
							// Send the request for the episode to TMDB
							$tmdbEpisode = $this->getEpisodeInfo(
								$tmdbid,
								$seasonNo,
								$episodeNo,
								$release['airdate']
							);

							if ($tmdbEpisode) {
								$episode = $this->addEpisode($videoId, $tmdbEpisode);
							}
						}

						if ($episode !== false && is_numeric($episode) && $episode > 0) {
							// Mark the releases video and episode IDs
							$this->setVideoIdFound($videoId, $row['id'], $episode);
							if ($this->echooutput) {
								echo $this->pdo->log->primary("Found TMDB Match!");
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
	 * Calls the API to perform initial show name match to TMDB title
	 * Returns a formatted array of show data or false if no match
	 *
	 * @param $cleanName
	 *
	 * @return array|bool
	 */
	protected function getShowInfo($cleanName)
	{
		$return = $response = false;
		var_dump($cleanName);

		try {
			//Try for the best match with AKAs embedded
			$response = $this->client->searchTVShow($cleanName);
		} catch (\Exception $error) {
		}

		sleep(1);

		if (is_array($response)) {
			$return = $this->processResponse($response, $cleanName);
		}
		if ($return === false) {
			try {
				//Try for the best match via full search (no AKAs can be returned)
				$response = $this->client->searchCollection($cleanName);
			} catch (\Exception $error) {
			}
			if (is_array($response)) {
				foreach ($response as $show) {
					$return = $this->processResponse($show, $cleanName);
				}
			}
		}
		return $return;
	}

	/**
	 * @param $show
	 * @param $cleanName
	 *
	 * @return array|bool
	 */
	private function processResponse ($show, $cleanName)
	{
		$return = false;

		if ($this->checkRequired($show, 'tmdbS')) {
			// Check for exact title match first and then terminate if found
			if ($show->name === $cleanName) {
				$return = $this->formatShowArr($show);
			} else {
				$return = $this->matchShowInfo($show, $cleanName);
			}
		}
		return $return;
	}

	private function matchShowInfo($show, $cleanName)
	{
		$return = false;
		$highestMatch = 0;

		// Check each show title for similarity and then find the highest similar value
		$matchPercent = $this->checkMatch($show->name, $cleanName, self::MATCH_PROBABILITY);

		// If new match has a higher percentage, set as new matched title
		if ($matchPercent > $highestMatch) {
			$highestMatch = $matchPercent;
			$highest = $show;
		}

		// Check for show aliases and try match those too
		if (is_array($show->akas) && !empty($show->akas)) {
			foreach ($show->akas as $key => $name) {
				$matchPercent = $this->checkMatch($name, $cleanName, $matchPercent);
				if ($matchPercent > $highestMatch) {
					$highestMatch = $matchPercent;
					$highest = $show;
				}
			}
		}
		if (isset($highest)) {
			$return = $this->formatShowArr($highest);
		}
		return $return;
	}

	/**
	 * Retrieves the poster art for the processed show
	 *
	 * @param int $videoId -- the local Video ID
	 * @param int $showId  -- the TMDB ID
	 *
	 * @return null
	 */
	protected function getPoster($videoId, $showId = 0)
	{
		$ri = new ReleaseImage($this->pdo);

		// Try to get the Poster
		$hascover = $ri->saveImage($videoId, sprintf($this->posterUrl), $this->imgSavePath, '', '');

		// Mark it retrieved if we saved an image
		if ($hascover == 1) {
			$this->setCoverFound($videoId);
		}
	}

	/**
	 * Gets the specific episode info for the parsed release after match
	 * Returns a formatted array of episode data or false if no match
	 *
	 * @param integer $tmdbid
	 * @param integer $season
	 * @param integer $episode
	 * @param string  $airdate
	 * @param integer $videoId
	 *
	 * @return array|bool
	 */
	protected function getEpisodeInfo($tmdbid, $season, $episode, $airdate = '', $videoId = 0)
	{
		$return = $response = false;

		if ($videoId > 0) {
			try {
				$response = $this->client->getTVShow($tmdbid);
			} catch (\Exception $error) {
			}
		} else {
			try {
				$response = $this->client->getEpisode($tmdbid, $season, $episode);
			} catch (\Exception $error) {
			}
		}

		sleep(1);

		//Handle Single Episode Lookups
		if (is_object($response)) {
			if ($this->checkRequired($response, 'tmdbE')) {
				$return = $this->formatEpisodeArr($response);
			}
		} else if (is_array($response)) {
			//Handle new show/all episodes
			if ($videoId > 0) {
				foreach ($response as $singleEpisode) {
					if ($this->checkRequired($singleEpisode, 'tmdbE')) {
						$this->addEpisode($videoId, $this->formatEpisodeArr($singleEpisode));
					}
				}
				//Handle airdate lookups -- return first response
			} else {
				if ($this->checkRequired($response[0], 'tmdbE')) {
					$return = $this->formatEpisodeArr($response[0]);
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
		$this->posterUrl = (string)(isset($show->_data['poster_path']) ? $show->_data['poster_path'] : '');

		return [
			'tmdbid'    => (int)$show->_data['id'],
			'column'    => 'tmdb',
			'siteid'    => (int)$show->_data['id'],
			'title'     => (string)$show->_data['name'],
			'summary'   => (string)$show->_data['overview'],
			'started'   => (string)$show->_data['first_air_date'],
			'publisher' => (string)$show->_data['networks']->name,
			'country'   => (string)$show->_data['origin_country'],
			'source'    => (int)parent::SOURCE_TMDB,
			'imdbid'    => 0,
			'tvdbid'    => 0,
			'traktid'   => 0,
			'tvrageid'  => 0,
			'tvmazeid'    => 0
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
		return [
			'title'       => (string)$episode->_data['name'],
			'series'      => (int)$episode->_data['season_number'],
			'episode'     => (int)$episode->_data['episode_number'],
			'se_complete' => (string)'S' . sprintf('%02d', $episode->_data['season_number']) . 'E' . sprintf('%02d', $episode->_data['episode_number']),
			'firstaired'  => (string)$episode->_data['air_date'],
			'summary'     => (string)$episode->_data['overview']
		];
	}
}
