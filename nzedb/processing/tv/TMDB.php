<?php
namespace nzedb\processing\tv;

use libs\Tmdb\TmdbAPI;
use nzedb\ReleaseImage;

class TMDB extends TV
{
	const MATCH_PROBABILITY = 75;


	/**
	 * @string DateTimeZone Object - UTC
	 */
	private $timeZone;

	/**
	 * @string MySQL DATETIME Format
	 */
	private $timeFormat;

	/**
	 * @var string The URL for the image for poster
	 */
	private $posterUrl;

	/**
	 * Construct. Instantiate TMDB Class
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new TmdbAPI($this->pdo->getSetting('tmdbkey'));
		$this->timeZone = new \DateTimeZone('UTC');
		$this->timeFormat = 'Y-m-d H:i:s';
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

		if ($this->echooutput && $tvcount > 0) {
			echo $this->pdo->log->header("Processing TMDB lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $row) {

				$this->posterUrl = '';
				$tmdbid = false;

				// Clean the show name for better match probability
				$release = $this->parseShowInfo($row['searchname']);

				if (is_array($release) && $release['name'] != '') {

					// Find the Video ID if it already exists by checking the title against stored TMDB titles
					$videoId = $this->getByTitle($release['cleanname'], parent::TYPE_TV, parent::SOURCE_TMDB);

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					} else {
						$lookupSetting = true;
					}

					// If lookups are allowed lets try to get it.
					if ($videoId === false && $lookupSetting) {
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Checking TMDB for previously failed title: ") .
									$this->pdo->log->headerOver($release['cleanname']) .
									$this->pdo->log->primary(".");
						}

						// Get the show from TMDB
						$tmdbShow = $this->getShowInfo((string)$release['cleanname']);

						if (is_array($tmdbShow)) {
							// Check if we have the TMDB ID already, if we do use that Video ID
							$dupeCheck = $this->getVideoIDFromSiteID('tvdb', $tmdbShow['tvdb']);
							if ($dupeCheck === false) {
								$videoId = $this->add($tmdbShow);
								$tmdbid = $tmdbShow['tmdb'];
							} else {
								$videoId = $dupeCheck;
								// Update any missing fields and add site IDs
								$this->update($videoId, $tmdbShow);
								$tmdbid = $this->getSiteIDFromVideoID('tmdb', $videoId);
							}
						}
					} else {
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Found local TMDB match for: ") .
									$this->pdo->log->headerOver($release['cleanname']) .
									$this->pdo->log->primary(".  Attempting episode lookup!");
						}
						$tmdbid = $this->getSiteIDFromVideoID('tmdb', $videoId);
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

						// Download all episodes if new show to reduce API usage
						if ($this->countEpsByVideoID($videoId) === false) {
							$this->getEpisodeInfo($tmdbid, -1, -1, '', $videoId);
						}

						// Check if we have the episode for this video ID
						$episode = $this->getBySeasonEp($videoId, $seasonNo, $episodeNo, $release['airdate']);

						if ($episode === false) {
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

		$response = $this->client->searchTVShow($cleanName);

		sleep(1);

		if (is_array($response) && !empty($response)) {
			$return = $this->matchShowInfo($response, $cleanName);
		}
		return $return;
	}

	/**
	 * @param $showArr
	 * @param $cleanName
	 *
	 * @return array|bool
	 */
	private function matchShowInfo($showArr, $cleanName)
	{
		$return = false;
		$highestMatch = 0;

		foreach ($showArr AS $show) {
			if ($this->checkRequired($show->_data, 'tmdbS')) {
				// Check for exact title match first and then terminate if found
				if (strtolower($show->_data['name']) === strtolower($cleanName)) {
					$highest = $show;
					break;
				} else {
					// Check each show title for similarity and then find the highest similar value
					$matchPercent = $this->checkMatch(strtolower($show->_data['name']), strtolower($cleanName), self::MATCH_PROBABILITY);

					// If new match has a higher percentage, set as new matched title
					if ($matchPercent > $highestMatch) {
						$highestMatch = $matchPercent;
						$highest = $show;
					}
				}
			}
		}
		if (isset($highest)) {
			$showAppends = $this->client->getTVShow($highest->_data['id'], 'append_to_response=alternative_titles,external_ids');
			if ($showAppends) {
				foreach ($showAppends->_data['alternative_titles']['results'] AS $aka) {
					$highest->_data['alternative_titles'][] = $aka['title'];
				}
				$highest->_data['network'] = (isset($showAppends->_data['networks'][0]['name']) ? $showAppends->_data['networks'][0]['name'] : '');
				$highest->_data['external_ids'] = $showAppends->_data['external_ids'];
			}
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
		$return = false;

		$response = $this->client->getEpisode($tmdbid, $season, $episode);

		sleep(1);

		//Handle Single Episode Lookups
		if (is_object($response)) {
			if ($this->checkRequired($response->_data, 'tmdbE')) {
				$return = $this->formatEpisodeArr($response);
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
		$this->posterUrl = $this->client->getImageURL() . (string)(isset($show->_data['poster_path']) ? $show->_data['poster_path'] : '');

		if (isset($show->_data['external_ids']['imdb_id'])) {
			preg_match('/tt(?P<imdbid>\d{6,7})$/i', $show->_data['external_ids']['imdb_id'], $imdb);
		}

		return [
				'type'      => (int)parent::TYPE_TV,
				'title'     => (string)$show->_data['name'],
				'summary'   => (string)$show->_data['overview'],
				'started'   => (string)$show->_data['first_air_date'],
				'publisher' => (isset($show->_data['network']) ? (string)$show->_data['network'] : ''),
				'country'   => (string)$show->_data['origin_country'][0],
				'source'    => (int)parent::SOURCE_TMDB,
				'imdb'      => (isset($imdb['imdbid']) ? (int)$imdb['imdbid'] : 0),
				'tvdb'      => (isset($show->_data['external_ids']['tvdb_id']) ? (int)$show->_data['external_ids']['tvdb_id'] : 0),
				'trakt'     => 0,
				'tvrage'    => (isset($show->_data['external_ids']['tvrage_id']) ? (int)$show->_data['external_ids']['tvrage_id'] : 0),
				'tvmaze'    => 0,
				'tmdb'      => (int)$show->_data['id'],
				'aliases'   => (!empty($show->_data['alternative_titles']) ? (array)$show->_data['alternative_titles'] : '')
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
