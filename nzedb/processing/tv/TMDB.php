<?php
namespace nzedb\processing\tv;

use app\models\Settings;
use nzedb\ColorCLI;
use nzedb\ReleaseImage;
use Tmdb\ApiToken;
use Tmdb\Client;
use Tmdb\Exception\TmdbApiException;
use Tmdb\Helper\ImageHelper;
use Tmdb\Repository\ConfigurationRepository;

class TMDB extends TV
{
	const MATCH_PROBABILITY = 75;

	/**
	 * @var string The URL for the image for poster
	 */
	public $posterUrl;

	/**
	 * @var ApiToken
	 */
	public $token;

	/**
	 * @var Client
	 */
	public $client;

	/**
	 * @var ImageHelper
	 */
	public $helper;

	/**
	 * @var ConfigurationRepository
	 */
	public $configRepository;

	/**
	 * @var \Tmdb\Model\Configuration
	 */
	public $config;

	/**
	 * Construct. Instantiate TMDB Class
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 * @throws \Exception
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->token = new ApiToken(Settings::value('APIs..tmdbkey'));
		$this->client = new Client($this->token, [
				'cache' => [
					'enabled' => false
				]
			]
		);
		$this->configRepository = new ConfigurationRepository($this->client);
		$this->config = $this->configRepository->load();
		$this->helper = new ImageHelper($this->config);
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
	 * @param string $groupID
	 * @param string $guidChar
	 * @param      $process
	 * @param bool $local
	 */
	public function processSite($groupID, $guidChar, $process, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $process, parent::PROCESS_TMDB);

		$tvcount = $res->rowCount();
		$lookupSetting = true;

		if ($this->echooutput && $tvcount > 0) {
			echo ColorCLI::header('Processing TMDB lookup for ' . number_format($tvcount) . ' release(s).');
		}

		if ($res instanceof \Traversable) {

			$this->titleCache = [];

			foreach ($res as $row) {

				$this->posterUrl = '';
				$tmdbid = false;

				// Clean the show name for better match probability
				$release = $this->parseInfo($row['searchname']);

				if (is_array($release) && $release['name'] !== '') {

					if (in_array($release['cleanname'], $this->titleCache, false)) {
						if ($this->echooutput) {
							echo ColorCLI::headerOver('Title: ') .
								ColorCLI::warningOver($release['cleanname']) .
								ColorCLI::header(' already failed lookup for this site.  Skipping.');
						}
						$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
						continue;
					}

					// Find the Video ID if it already exists by checking the title against stored TMDB titles
					$videoId = $this->getByTitle($release['cleanname'], parent::TYPE_TV, parent::SOURCE_TMDB);

					// Force local lookup only
					if ($local === true) {
						$lookupSetting = false;
					}

					// If lookups are allowed lets try to get it.
					if ($videoId === false && $lookupSetting) {
						if ($this->echooutput) {
							echo ColorCLI::primaryOver('Checking TMDB for previously failed title: ') .
								ColorCLI::headerOver($release['cleanname']) .
								ColorCLI::primary('.');
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
							echo ColorCLI::primaryOver('Found local TMDB match for: ') .
								ColorCLI::headerOver($release['cleanname']) .
								ColorCLI::primary('.  Attempting episode lookup!');
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
							echo ColorCLI::primary('Found TMDB Match for Full Season!');
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
								echo ColorCLI::primary('Found TMDB Match!');
							}
							continue;
						}
						//Processing failed, set the episode ID to the next processing group
						$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
					} else {
						//Processing failed, set the episode ID to the next processing group
						$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
						$this->titleCache[] = $release['cleanname'];
					}
				} else{
					//Processing failed, set the episode ID to the next processing group
					$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
					$this->titleCache[] = $release['cleanname'];
				}
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

		try {
			$response = $this->client->getTvApi()->getTvshow($cleanName);
		} catch (TmdbApiException $e) {
			return false;
		}

		sleep(1);

		if (is_array($response) && !empty($response['results'])) {
			$return = $this->matchShowInfo($response['results'], $cleanName);
		}
		return $return;
	}

	/**
	 * @param array $shows
	 * @param string $cleanName
	 *
	 * @return array|bool
	 */
	private function matchShowInfo($shows, $cleanName)
	{
		$return = false;
		$highestMatch = 0;

		$show = [];
		foreach ($shows AS $show) {
			if ($this->checkRequiredAttr($show, 'tmdbS')) {
				// Check for exact title match first and then terminate if found
				if (strtolower($show['name']) === strtolower($cleanName)) {
					$highest = $show;
					break;
				}
				// Check each show title for similarity and then find the highest similar value
				$matchPercent = $this->checkMatch(strtolower($show['name']), strtolower($cleanName), self::MATCH_PROBABILITY);

				// If new match has a higher percentage, set as new matched title
				if ($matchPercent > $highestMatch) {
					$highestMatch = $matchPercent;
					$highest = $show;
				}
			}
		}
		if (!empty($highest)) {
			try {
				$showAlternativeTitles = $this->client->getTvApi()->getAlternativeTitles($highest['id']);
			} catch (TmdbApiException $e) {
				return false;
			}

			try {
				$showExternalIds = $this->client->getTvApi()->getExternalIds($highest['id']);
			} catch (TmdbApiException $e) {
				return false;
			}

			if ($showAlternativeTitles !== null && is_array($showAlternativeTitles)) {
				foreach ($showAlternativeTitles AS $aka) {
					$highest['alternative_titles'][] = $aka['title'];
				}
				$highest['network'] = isset($show['networks'][0]['name']) ? $show['networks'][0]['name'] : '';
				$highest['external_ids'] = $showExternalIds;
			}
			$return = $this->formatShowInfo($highest);
		}
		return $return;
	}

	/**
	 * Retrieves the poster art for the processed show
	 *
	 * @param int $videoId -- the local Video ID
	 * @param int $showId  -- the TMDB ID
	 *
	 * @return int
	 */
	public function getPoster($videoId, $showId = 0)
	{
		$ri = new ReleaseImage($this->pdo);

		// Try to get the Poster
		$hascover = $ri->saveImage($videoId, $this->posterUrl, $this->imgSavePath);

		// Mark it retrieved if we saved an image
		if ($hascover === 1) {
			$this->setCoverFound($videoId);
		}
		return $hascover;
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

		try {
			$response = $this->client->getTvEpisodeApi()->getEpisode($tmdbid, $season, $episode);
		} catch (TmdbApiException $e) {
			return false;
		}

		sleep(1);

		//Handle Single Episode Lookups
		if (is_array($response) && $this->checkRequiredAttr($response, 'tmdbE')) {
			$return = $this->formatEpisodeInfo($response);
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
	protected function formatShowInfo($show)
	{
		$this->posterUrl = isset($show['poster_path']) ? 'https:' . $this->helper->getUrl($show['poster_path']) : '';

		if (isset($show['external_ids']['imdb_id'])) {
			preg_match('/tt(?P<imdbid>\d{6,7})$/i', $show['external_ids']['imdb_id'], $imdb);
		}

		return [
			'type'      => parent::TYPE_TV,
			'title'     => (string)$show['name'],
			'summary'   => (string)$show['overview'],
			'started'   => (string)$show['first_air_date'],
			'publisher' => isset($show['network']) ? (string)$show['network'] : '',
			'country'   => isset($show['origin_country'][0]) ? $show['origin_country'][0] : '',
			'source'    => parent::SOURCE_TMDB,
			'imdb'      => isset($imdb['imdbid']) ? (int)$imdb['imdbid'] : 0,
			'tvdb'      => isset($show['external_ids']['tvdb_id']) ? (int)$show['external_ids']['tvdb_id'] : 0,
			'trakt'     => 0,
			'tvrage'    => isset($show['external_ids']['tvrage_id']) ? (int)$show['external_ids']['tvrage_id'] : 0,
			'tvmaze'    => 0,
			'tmdb'      => (int)$show['id'],
			'aliases'   => !empty($show['alternative_titles']) ? (array)$show['alternative_titles'] : '',
			'localzone' => "''"
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
	protected function formatEpisodeInfo($episode)
	{
		return [
			'title'       => (string)$episode['name'],
			'series'      => (int)$episode['season_number'],
			'episode'     => (int)$episode['episode_number'],
			'se_complete' => 'S' . sprintf('%02d', $episode['season_number']) . 'E' . sprintf('%02d', $episode['episode_number']),
			'firstaired'  => (string)$episode['air_date'],
			'summary'     => (string)$episode['overview']
		];
	}
}
