<?php
namespace nzedb\processing\tv;

use nzedb\libraries\TraktAPI;
use nzedb\ReleaseImage;

/**
 * Class TraktTv
 *
 * Process information retrieved from the Trakt API.
 */
class TraktTv extends TV
{
	const MATCH_PROBABILITY = 75;

	/**
	 * Client for Trakt API
	 *
	 * @var \nzedb\libraries\TraktAPI
	 */
	public $client;

	/**
	 * The Trakt.tv API v2 Client ID (SHA256 hash - 64 characters long string). Used for movie and tv lookups.
	 * Create one here: https://trakt.tv/oauth/applications/new
	 *
	 * @var array|bool|string
	 */
	private $clientId;

	/**
	 * List of headers to send to Trakt.tv when making a request.
	 *
	 * @see http://docs.trakt.apiary.io/#introduction/required-headers
	 * @var array
	 */
	private $requestHeaders;

	/**
	 * Construct. Set up API key.
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->clientId = $this->pdo->getSetting('trakttvclientkey');
		$this->requestHeaders = [
				'Content-Type: application/json',
				'trakt-api-version: 2',
				'trakt-api-key: ' . $this->clientId,
				'Content-Length: 0'
		];
		$this->client = new TraktAPI($this->requestHeaders);
	}

	/**
	 * Main processing director function for scrapers
	 * Calls work query function and initiates processing
	 *
	 * @param      $groupID
	 * @param      $guidChar
	 * @param      $process
	 * @param bool $local
	 */
	public function processSite($groupID, $guidChar, $process, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $process, parent::PROCESS_TRAKT);

		$tvcount = $res->rowCount();

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TRAKT lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $row) {

				$traktid = false;

				// Clean the show name for better match probability
				$release = $this->parseInfo($row['searchname']);
				if (is_array($release) && $release['name'] != '') {

					// Find the Video ID if it already exists by checking the title.
					$videoId = $this->getByTitle($release['cleanname'], parent::TYPE_TV);

					if ($videoId !== false) {
						$traktid = $this->getSiteByID('trakt', $videoId);
					}

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					} else {
						$lookupSetting = true;
					}

					if ($traktid === false && $lookupSetting) {

						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
									$this->pdo->log->headerOver($release['cleanname']) .
									$this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TRAKT
						$traktShow = $this->getShowInfo((string)$release['cleanname']);

						if (is_array($traktShow)) {
							$videoId = $this->add($traktShow);
							$traktid = (int)$traktShow['show']['ids']['trakt'];
						}

					} else if ($this->echooutput) {
						echo $this->pdo->log->primaryOver("Video ID for ") .
								$this->pdo->log->headerOver($release['cleanname']) .
								$this->pdo->log->primary(" found in local db, attempting episode match.");
					}

					if (is_numeric($videoId) && $videoId > 0 && is_numeric($traktid) && $traktid > 0) {
						// Now that we have valid video and trakt ids, try to get the poster
						$this->getPoster($videoId, $traktid);

						$seasonNo = preg_replace('/^S0*/i', '', $release['season']);
						$episodeNo = preg_replace('/^E0*/i', '', $release['episode']);

						if ($episodeNo === 'all') {
							// Set the video ID and leave episode 0
							$this->setVideoIdFound($videoId, $row['id'], 0);
							echo $this->pdo->log->primary("Found TRAKT Match for Full Season!");
							continue;
						}

						// Check if we have the episode for this video ID
						$episode = $this->getBySeasonEp($videoId, $seasonNo, $episodeNo, $release['airdate']);

						if ($episode === false && $lookupSetting) {
							// Send the request for the episode to TRAKT
							$traktEpisode = $this->getEpisodeInfo(
									$traktid,
									$seasonNo,
									$episodeNo
							);

							if ($traktEpisode) {
								$episode = $this->addEpisode($videoId, $traktEpisode);
							}
						}

						if ($episode !== false && is_numeric($episode) && $episode > 0) {
							// Mark the releases video and episode IDs
							$this->setVideoIdFound($videoId, $row['id'], $episode);
							if ($this->echooutput) {
								echo $this->pdo->log->primary("Found TRAKT Match!");
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
	 * Retrieve info of TV episode from site using its API.
	 *
	 * @param integer $siteId
	 * @param integer $series
	 * @param integer $episode
	 *
	 * @return array|false False on failure, an array of information fields otherwise.
	 */
	public function getEpisodeInfo($siteId, $series, $episode)
	{
		$return = false;

		$response = $this->client->episodeSummary($siteId, $series, $episode);

		sleep(1);

		if (is_array($response)) {
			if ($this->checkRequiredAttr($response, 'traktE')) {
				$return = $this->formatEpisodeInfo($response);
			}
		}

		return $return;
	}

	public function getMovieInfo()
	{
		;
	}

	/**
	 * Retrieve poster image for TV episode from site using its API.
	 *
	 * @param integer $videoId ID from videos table.
	 * @param integer $siteId  ID that this site uses for the programme.
	 *
	 * @return null
	 */
	public function getPoster($videoId, $siteId)
	{
		$ri = new ReleaseImage($this->pdo);

		$poster = $this->client->showSummary($siteId, 'images');

		// Try to get the Poster
		$hascover = $ri->saveImage($videoId, $poster['images']['poster']['thumb'], $this->imgSavePath, '', '');

		// Couldn't get poster, try fan art instead
		if ($hascover !== 1) {
			$hascover = $ri->saveImage($videoId, $poster['images']['fanart']['thumb'], $this->imgSavePath, '', '');
		}
		// Mark it retrieved if we saved an image
		if ($hascover == 1) {
			$this->setCoverFound($videoId);
		}
	}

	/**
	 * Retrieve info of TV programme from site using it's API.
	 *
	 * @param string $name Title of programme to look up. Usually a cleaned up version from releases table.
	 *
	 * @return array|false    False on failure, an array of information fields otherwise.
	 */
	public function getShowInfo($name)
	{
		$return = $response = false;
		$highestMatch = 0;
		$response = (array)$this->client->showSummary($name, 'full');

		sleep(1);

		if (is_array($response)) {
			foreach ($response as $show) {
				if ($this->checkRequiredAttr($show, 'traktS')) {
					// Check for exact title match first and then terminate if found
					if ($show->name === $name) {
						$highest = $show;
						break;
					}

					// Check each show title for similarity and then find the highest similar value
					$matchPercent = $this->checkMatch($show->name, $name, self::MATCH_PROBABILITY);

					// If new match has a higher percentage, set as new matched title
					if ($matchPercent > $highestMatch) {
						$highestMatch = $matchPercent;
						$highest = $show;
					}

					// Check for show aliases and try match those too
					if (!empty($show->aliasNames)) {
						foreach ($show->aliasNames as $key => $name) {
							$matchPercent = $this->CheckMatch($name, $name, $matchPercent);
							if ($matchPercent > $highestMatch) {
								$highestMatch = $matchPercent;
								$highest = $show;
							}
						}
					}
				}
			}
			if (isset($highest)) {
				$return = $this->formatShowInfo($highest);
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
	public function formatShowInfo($show)
	{
		preg_match('/tt(?P<imdbid>\d{6,7})$/i', $show->ids->imdb, $imdb);

		return [
				'type'      => (int)parent::TYPE_TV,
				'title'     => (string)$show->title,
				'summary'   => (string)$show->overview,
				'started'   => (string)$show->first_aired,
				'publisher' => (string)$show->network,
				'source'    => (int)parent::SOURCE_TRAKT,
				'imdb'      => (int)(isset($imdb['imdbid']) ? $imdb['imdbid'] : 0),
				'tvdb'      => 0,
				'trakt'     => (int)$show->ids->trakt,
				'tvrage'    => (int)(isset($show->ids->tvrage) ? $show->ids->tvrage : 0),
				'tvmaze'    => 0,
				'tmdb'      => (int)(isset($show->ids->tmdb) ? $show->ids->tmdb : 0),
				'aliases'   => (!empty($show->aliasNames) ? (array)$show->aliasNames : '')
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
	public function formatEpisodeInfo($episode)
	{
		return [
				'title'       => (string)$episode->title,
				'series'      => (int)$episode->season,
				'episode'     => (int)$episode->number,
				'se_complete' => (string)'S' . sprintf('%02d', $episode->season) . 'E' . sprintf('%02d', $episode->number),
				'firstaired'  => (string)$episode->first_aired,
				'summary'     => (string)$episode->overview
		];
	}
}
