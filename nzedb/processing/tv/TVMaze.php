<?php
namespace nzedb\processing\tv;

use \libs\JPinkney\TVMaze\Client;
use nzedb\ReleaseImage;

/**
 * Class TVMaze
 *
 * Process information retrieved from the TVMaze API.
 */
class TVMaze extends TV
{
	const MATCH_PROBABILITY = 75;

	/**
	 * Client for TVMaze API
	 *
	 * @var \libs\JPinkney\TVMaze\Client
	 */
	public $client;

	/**
	 * @var string The URL for the medium sized image for poster
	 */
	private $posterUrl;

	/**
	 * Construct. Instanciate TVMaze Client Class
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new Client();
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
	 * Main processing director function for TVMaze
	 * Calls work query function and initiates processing
	 *
	 * @param            $groupID
	 * @param            $guidChar
	 * @param            $processTV
	 * @param bool|false $local
	 */
	public function processTVMaze ($groupID, $guidChar, $processTV, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $processTV, parent::PROCESS_TVMAZE);

		$tvcount = $res->rowCount();

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TVMaze lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $row) {

				$tvmazeid = false;
				$this->posterUrl = '';

				// Clean the show name for better match probability
				$release = $this->parseNameEpSeason($row['searchname']);
				if (is_array($release) && $release['name'] != '') {

					// Find the Video ID if it already exists by checking the title.
					$videoId = $this->getByTitle($release['cleanname']);

					if ($videoId !== false) {
						$tvmazeid = $this->getSiteByID('tvmaze', $videoId);
					}

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					} else {
						$lookupSetting = true;
					}

					if ($tvmazeid === false && $lookupSetting) {

						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo	$this->pdo->log->primaryOver("Video ID for ") .
								$this->pdo->log->headerOver($release['cleanname']) .
								$this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TVDB
						$tvmazeShow = $this->getShowInfo((string)$release['cleanname']);

						if (is_array($tvmazeShow)) {
							$videoId = $this->add($tvmazeShow);
							$tvmazeid = (int)$tvmazeShow['tvmazeid'];
						}
					} else if ($this->echooutput) {
						echo $this->pdo->log->primaryOver("Video ID for ") .
							$this->pdo->log->headerOver($release['cleanname']) .
							$this->pdo->log->primary(" found in local db, attempting episode match.");
					}

					if (is_numeric($videoId) && $videoId > 0 && is_numeric($tvmazeid) && $tvmazeid > 0) {
						// Now that we have valid video and tvmaze ids, try to get the poster
						$this->getPoster($videoId, $tvmazeid);

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
							$this->getEpisodeInfo($tvmazeid, -1, -1, '', $videoId);
						}

						// Check if we have the episode for this video ID
						$episode = $this->getBySeasonEp($videoId, $seasonNo, $episodeNo, $release['airdate']);

						if ($episode === false && $lookupSetting) {
							// Send the request for the episode to TVDB
							$tvmazeEpisode = $this->getEpisodeInfo(
								$tvmazeid,
								$seasonNo,
								$episodeNo,
								$release['airdate']
							);

							if ($tvmazeEpisode) {
								$episode = $this->addEpisode($videoId, $tvmazeEpisode);
							}
						}

						if ($episode !== false && is_numeric($episode) && $episode > 0) {
							// Mark the releases video and episode IDs
							$this->setVideoIdFound($videoId, $row['id'], $episode);
							if ($this->echooutput) {
								echo $this->pdo->log->primary("Found TVMaze Match!");
							}
							continue;
						}
					}
				} //Processing failed, set the episode ID to the next processing group
				$this->setVideoNotFound(parent::PROCESS_IMDB, $row['id']);
			}
		}
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

		try {
			//Try for the best match with AKAs embedded
			$response = $this->client->singleSearch($cleanName);
		} catch (\Exception $error) {
		}

		sleep(1);

		if (is_array($response)) {
			$return = $this->processResponse($response, $cleanName);
		}
		if ($return === false) {
			try {
				//Try for the best match via full search (no AKAs can be returned)
				$response = $this->client->search($cleanName);
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

		if ($this->checkRequired($show, 'tvmazeS')) {
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
	 * @param int $showId  -- the TVDB ID
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
	 * @param integer $tvmazeid
	 * @param integer $season
	 * @param integer $episode
	 * @param string  $airdate
	 * @param integer $videoId
	 *
	 * @return array|bool
	 */
	protected function getEpisodeInfo($tvmazeid, $season, $episode, $airdate = '', $videoId = 0)
	{
		$return = $response = false;

		if ($airdate !== '') {
			try {
				$response = $this->client->getEpisodesByAirdate($tvmazeid, $airdate);
			} catch (\Exception $error) {
			}
		} else if ($videoId > 0) {
			try {
				$response = $this->client->getEpisodesByShowID($tvmazeid);
			} catch (\Exception $error) {
			}
		} else {
			try {
				$response = $this->client->getEpisodeByNumber($tvmazeid, $season, $episode);
			} catch (\Exception $error) {
			}
		}

		sleep(1);

		if (is_object($response)) {
			if ($this->checkRequired($response, 'tvmazeE')) {
				$return = $this->formatEpisodeArr($response);
			}
		} else if (is_array($response) && isset($response['episodes']) && $videoId > 0) {
			foreach ($response['episodes'] as $singleEpisode) {
				if ($this->checkRequired($singleEpisode, 'tvmazeE')) {
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
		$this->posterUrl = (string)(isset($show->mediumImage) ? $show->mediumImage : '');

		return [
			'tvmazeid'    => (int)$show->id,
			'column'    => 'tvmaze',
			'siteid'    => (int)$show->id,
			'title'     => (string)$show->name,
			'summary'   => (string)$show->summary,
			'started'   => (string)$show->premiered,
			'publisher' => (string)$show->network,
			'country'   => (string)$show->country,
			'source'    => (int)parent::SOURCE_TVMAZE,
			'imdbid'    => 0,
			'tvdbid'    => (int)(isset($show->externalIDs['thetvdb']) ? $show->externalIDs['thetvdb'] : 0),
			'traktid'   => 0,
			'tvrageid'  => (int)(isset($show->externalIDs['tvrage']) ? $show->externalIDs['tvrage'] : 0),
			'tmdbid'    => 0
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
			'title'       => (string)$episode->name,
			'series'      => (int)$episode->season,
			'episode'     => (int)$episode->number,
			'se_complete' => (string)'S' . sprintf('%02d', $episode->season) . 'E' . sprintf('%02d', $episode->number),
			'firstaired'  => (string)$episode->airdate,
			'summary'     => (string)$episode->summary
		];
	}
}