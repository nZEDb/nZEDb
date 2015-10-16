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
	 * @string
	 */
	public $posterUrl;

	/**
	 * @string
	 */
	public $imgSavePath;

	/**
	 * @string
	 */
	public $serverTime;

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new Client(self::TVDB_URL, self::TVDB_API_KEY);
		$this->posterUrl = self::TVDB_URL . DS . 'banners/_cache/posters/%s-1.jpg';
		$this->imgSavePath = nZEDb_COVERS . 'tvdb' . DS;
		$this->serverTime = $this->client->getServerTime();
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
					$video = $this->getByTitle($release['cleanname']);

					if ($video !== 0) {
						$tvdbid = $this->getSiteByID('tvdb', $video);
					}

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					} else {
						$lookupSetting = true;
					}

					if ($tvdbid == false && $lookupSetting) {

						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo	$this->pdo->log->primaryOver("Video ID for ") .
									$this->pdo->log->headerOver($release['cleanname']) .
									$this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TVDB
						$tvdbShow = $this->getTVDBShow((string)$release['cleanname']);

						if (is_array($tvdbShow)) {
							$tvdbShow['hascover'] = $this->getTVDBPoster($tvdbShow);
							$tvdbShow['country']  = (isset($release['country']) ? (string) $release['country'] : '');

							$video = $this->add(
										$tvdbShow['column'],
										$tvdbShow['siteid'],
										$tvdbShow['title'],
										$tvdbShow['summary'],
										$tvdbShow['country'],
										$tvdbShow['started'],
										$tvdbShow['publisher'],
										$tvdbShow['hascover'],
										$tvdbShow['source']
							);
							$tvdbid = $tvdbShow['siteid'];
						}
					} else if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($release['cleanname']) .
								 $this->pdo->log->primary(" found in local db, only attempting episode lookup.");
					}

					if (is_numeric($video) && $video > 0 && is_numeric($tvdbid) && $tvdbid > 0) {

						// Check first if we have the episode for this video
						$episode = $this->getBySeasonEp($video, $release['season'], $release['episode']);

						if ($episode === false && $lookupSetting) {
							// Send the request for the episode
							$tvdbEpisode = $this->getTVDBEpisode($tvdbid, $release['season'], $release['episode']);

							if (is_array($tvdbEpisode)) {
								$episode = $this->addEpisode(
												$video,
												$tvdbEpisode['season'],
												$tvdbEpisode['episode'],
												$tvdbEpisode['se_complete'],
												$tvdbEpisode['title'],
												$tvdbEpisode['firstaired'],
												$tvdbEpisode['summary']
								);
							}
						}

						if (is_numeric($episode) && $episode > 0) {
							// Mark the releases video and episode IDs
							$this->setVideoIdFound($video, $row['id'], $episode);
							if ($this->echooutput) {
								echo	$this->pdo->log->primaryOver("Found TVDB Match!");
							}
						}

					} else {
						// Processing failed, set the episode ID to the next processing group
						$this->setVideoNotFound(parent::PROCESS_TRAKT, $row['id']);
					}
				}
			}
		}
	}

	/**
	 * @param $cleanName
	 *
	 * @return array|bool
	 */
	private function getTVDBShow($cleanName)
	{
		$return = false;
		$highestMatch = 0;
		$response = (array)$this->client->getSeries($cleanName);
		usleep(500);

		if (is_array($response)) {
			foreach ($response as $show) {
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
			}
			if (isset($highest)) {
				$return = $this->formatShowArr($highest);
			}
		}
		return $return;
	}

	/**
	 * @param $show
	 *
	 * @return int
	 */
	private function getTVDBPoster($show)
	{
		return (new ReleaseImage($this->pdo))->saveImage($show['siteid'], $show['imgurl'], $this->imgSavePath, '', '');
	}

	/**
	 * @param $tvdbid
	 * @param $season
	 * @param $episode
	 *
	 * @return array|bool
	 */
	private function getTVDBEpisode($tvdbid, $season, $episode)
	{
		$return = false;
		$response = $this->client->getEpisode($tvdbid, $season, $episode);

		if (is_object($response)) {
			$return = $this->formatEpisodeArr($response);
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
		return	[
					'column'    => (string)'tvdb',
					'siteid'    => (int)$show->id,
					'title'     => (string)$show->name,
					'summary'   => (string)$show->overview,
					'started'   => (string)$show->firstAired->format('m-d-Y'),
					'publisher' => (string)$show->network,
					'source'    => (int)parent::SOURCE_TVDB,
					'imgurl'    => (string)sprintf($this->posterUrl, $show->id)
				];
	}

	/**
	 * @param $episode
	 *
	 * @return array
	 */
	private function formatEpisodeArr($episode)
	{
		return [
			'title'       => (string)$episode->name,
			'season'      => (int)$episode->season,
			'episode'     => (int)$episode->number,
			'se_complete' => (string)'S' . sprintf('%03d', $episode->season) . 'E' . sprintf('%03d', $episode->number),
			'firstaired'  => (string)$episode->firstAired->format('m-d-Y'),
			'summary'     => (string)$episode->overview
		];
	}
}