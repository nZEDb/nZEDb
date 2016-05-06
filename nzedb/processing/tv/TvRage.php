<?php
namespace nzedb\processing\tv;

use nzedb\utility\Misc;
use nzedb\utility\Country;
use nzedb\ReleaseImage;

/**
 * Class TvRage - functions used to post process releases against TvRage
 */
class TvRage extends TV
{

	const APIKEY = '7FwjZ8loweFcOhHfnU3E';
	const MATCH_PROBABILITY = 75;

	/**
	 * @var string
	 */
	public $showInfoUrl         = 'http://www.tvrage.com/shows/id-';

	/**
	 * @var string
	 */
	public $showQuickInfoURL    = 'http://services.tvrage.com/tools/quickinfo.php?show=';

	/**
	 * @var string
	 */
	public $xmlFullSearchUrl    = 'http://services.tvrage.com/feeds/full_search.php?show=';

	/**
	 * @var string
	 */
	public $xmlShowInfoUrl      = 'http://services.tvrage.com/feeds/showinfo.php?sid=';

	/**
	 * @var string
	 */
	public $xmlFullShowInfoUrl  = 'http://services.tvrage.com/feeds/full_show_info.php?sid=';

	/**
	 * @var string
	 */
	public $xmlEpisodeInfoUrl;

	/**
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * @var int|bool
	 */
	private $videoId;

	/**
	 * @var string
	 */
	private $posterUrl;

	/**
	 * @var
	 */
	private $rageId;

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->xmlEpisodeInfoUrl = "http://services.tvrage.com/myfeeds/episodeinfo.php?key=" . TvRage::APIKEY;
		$this->imgSavePath = nZEDb_COVERS . 'tvrage' . DS;
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
	public function processSite($groupID = '', $guidChar = '', $process, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $local, parent::PROCESS_TVRAGE);

		$tvcount = $res->rowCount();

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TV Rage lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $arr) {
				$this->rageId = $this->rageId = false;
				$show = $this->parseInfo($arr['searchname']);
				if (is_array($show) && $show['name'] != '') {
					// Find the Video ID if it already exists by checking the title.
					$this->videoId = $this->getByTitle($show['cleanname'], parent::TYPE_TV);

					// Force local lookup only
					if ($local == true) {
						$process = false;
					}

					if ($this->videoId === false && $process) {
						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($show['cleanname']) .
								 $this->pdo->log->primary(" not found in local db, checking web.");
						}
						$tvrShow = $this->getShowInfo($show);
						if ($tvrShow !== false && is_array($tvrShow)) {
							// Get all tv info and add show.
							$this->rageId = $tvrShow['showid'];
							$this->updateRageInfo($this->rageId, $tvrShow);
						} else {
							$this->setVideoNotFound(parent::PROCESS_TVMAZE, $arr['id']);
						}
					}
					if ($this->videoId > 0) {
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($show['cleanname']) .
								 $this->pdo->log->primary(" found in local db, setting tvrage ID and attempting episode lookup.");
						}
						$episodeId = $this->getBySeasonEp($this->videoId, $show['season'], $show['episode']);
						if ($episodeId === false) {
							$epinfo = $this->getEpisodeInfo($this->rageId, $show['season'], $show['episode']);
							if ($epinfo !== false && isset($epinfo['airdate']) && !empty($epinfo['title'])) {
								$tvairdate = $this->pdo->escapeString($this->checkDate($epinfo['airdate']));
								$tvtitle = $this->pdo->escapeString(trim($epinfo['title']));
								$seComplete = 'S' . sprintf('%02s', $show['season']) . 'E' . sprintf('%02s', $show['episode']);
								$episodeId = $this->addEpisode($this->videoId,
									[
										'series'      => $show['season'],
										'episode'     => $show['episode'],
										'se_complete' => $seComplete,
										'firstaired'  => $tvairdate,
										'title'       => $tvtitle,
										'summary'     => ''
									]
								);
							}
						}
						echo $this->pdo->log->primary("Found TV Rage Match!");
						$this->setVideoIdFound($this->videoId, $arr['id'], $episodeId);
					// Cant find videos_id, so set tv_episodes_id to PROCESS_TVMAZE.
					} else {
						$this->setVideoNotFound(parent::NO_MATCH_FOUND, $arr['id']);
					}
				// Not a tv episode, so set videos_id to n/a.
				} else {
					$this->setVideoNotFound(parent::NO_MATCH_FOUND, $arr['id']);
				}
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
	 * Retrieves poster image from TvRage through ReleaseImage
	 *
	 * @param int $videoId
	 * @param int $siteId
	 *
	 * @return null
	 */
	protected function getPoster($videoId, $siteId)
	{
		$hascover = (new ReleaseImage($this->pdo))->saveImage(
			$videoId,
			$this->posterUrl,
			$this->imgSavePath,
			'',
			''
		);
		if ($hascover == 1) {
			$this->setCoverFound($videoId);
		}
	}

	/**
	 * Formats and inserts the TvRage match and calls the download of the cover image
	 *
	 * @param $rageid
	 * @param $tvrShow
	 */
	public function updateRageInfo($rageid, $tvrShow)
	{
		$country = '';

		if (isset($tvrShow['country']) && !empty($tvrShow['country'])) {
			$country = Country::countryCode($tvrShow['country'], $this->pdo);
		}

		$rInfo = $this->getRageInfoFromPage($rageid);
		$summary = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc'])) {
			$summary = $rInfo['desc'];
		}
		$this->videoId = $this->add(
			[
				'type'      => parent::TYPE_TV,
				'title'     => $tvrShow['title'],
				'column'    => 'tvrage',
				'siteid'    => $rageid,
				'summary'   => $summary,
				'country'   => $country,
				'started'   => $tvrShow['started'],
				'publisher' => $tvrShow['publisher'],
				'source'    => parent::SOURCE_TVRAGE,
				'tvdbid'    => 0,
				'traktid'   => 0,
				'tvrageid'  => $rageid,
				'tvmazeid'  => 0,
				'imdbid'    => 0,
				'tmdbid'    => 0,
				'aliases'   => $tvrShow['aliases']
			]
		);
		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
			$this->posterUrl = $rInfo['imgurl'];
			$this->getPoster($this->videoId, $this->rageId);
		}
	}

	/**
	 * Retrieves episode specific information once a RageID is matched
	 * Returns episode info array if matched, else false
	 *
	 * @param int $rageid
	 * @param int $series
	 * @param int $episode
	 *
	 * @return array|bool
	 */
	public function getEpisodeInfo($rageid, $series, $episode)
	{
		$result = false;

		$series = str_ireplace("s", "", $series);
		$episode = str_ireplace("e", "", $episode);
		$xml = Misc::getUrl(['url' => $this->xmlEpisodeInfoUrl . "&sid=" . $rageid . "&ep=" . $series . "x" . $episode]);
		if ($xml !== false) {
			if (stripos($xml, 'no show found') === false) {
				$xmlObj = @simplexml_load_string($xml);
				$arrXml = Misc::objectsIntoArray($xmlObj);
				if (is_array($arrXml)) {
					$result = [];
					if (isset($arrXml['episode']['airdate']) && $arrXml['episode']['airdate'] != '0000-00-00') {
						$result['airdate'] = $arrXml['episode']['airdate'];
					}
					if (isset($arrXml['episode']['title'])) {
						$result['title'] = $arrXml['episode']['title'];
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Scrapes additional fields not offered through the standard API
	 * Returns an array of additional params or false if no matched response
	 *
	 * @param string $rageid
	 *
	 * @return array|bool|mixed
	 */
	public function getRageInfoFromService($rageid)
	{
		$result = false;
		// Standard show info search as AKAs not needed.
		$xml = Misc::getUrl(['url' => $this->xmlShowInfoUrl . $rageid]);
		if ($xml !== false) {
			$arrXml = Misc::objectsIntoArray(simplexml_load_string($xml));
			if (is_array($arrXml)) {
				$result = ['showid' => $rageid];
				$result['country'] = (isset($arrXml['origin_country'])) ? $arrXml['origin_country'] : '';
				$result['firstaired'] = (isset($arrXml['startdate'])) ? date('m-d-Y', strtotime($arrXml['startdate'])) : '';
				$result = Country::countryCode($result, $this->pdo);
			}
		}
		return $result;
	}

	/**
	 * Queries for show description and the cover image info
	 * Returns an array of the additional parameters or seemingly useless array
	 *
	 * @param $rageid
	 *
	 * @return array
	 */
	public function getRageInfoFromPage($rageid)
	{
		$result = ['desc' => '', 'imgurl' => ''];
		$page = Misc::getUrl(['url' => $this->showInfoUrl . $rageid]);
		$matches = '';
		if ($page !== false) {
			// Description.
			preg_match('@<div class="show_synopsis">(.*?)</div>@is', $page, $matches);
			if (isset($matches[1])) {
				$desc = $matches[1];
				$desc = preg_replace('/<hr>.*/s', '', $desc);
				$desc = preg_replace('/&nbsp;?/', '', $desc);
				$desc = preg_replace('/<br>(\n)?<br>/', ' / ', $desc);
				$desc = preg_replace('/\n/', ' ', $desc);
				$desc = preg_replace('/<a href.*?<\/a>/', '', $desc);
				$desc = preg_replace('/<script.*?<\/script>/', '', $desc);
				$desc = preg_replace('/<.*?>/', '', $desc);
				$desc = str_replace('()', '', $desc);
				$desc = trim(preg_replace('/\s{2,}/', ' ', $desc));
				$result['desc'] = $desc;
			}
			// Image.
			preg_match("@src=[\"'](http://images.tvrage.com/shows.*?)[\"']@i", $page, $matches);
			if (isset($matches[1])) {
				$result['imgurl'] = $matches[1];
			}
		}
		return $result;
	}

	/**
	 * Main function for matching a releae searchname to a TvRage title
	 * Returns basic show information array or -1 int if no match
	 *
	 * @param $showInfo
	 *
	 * @return array|int
	 */
	public function getShowInfo($showInfo)
	{
		$matchedTitle = -1;
		$title = $showInfo['cleanname'];
		// Full search gives us the akas.
		$xml = Misc::getUrl(['url' => $this->xmlFullSearchUrl . urlencode(strtolower($title))]);
		if ($xml !== false) {
			$arrXml = @Misc::objectsIntoArray(simplexml_load_string($xml));

			// CheckXML Response is valid before processing
			if (isset($arrXml['show']) && is_array($arrXml)) {

				// We got exactly 1 match so lets convert it to an array so we can use it in the logic below.
				if (isset($arrXml['show']['showid'])) {
					$newArr[] = $arrXml['show'];
					unset($arrXml);
					$arrXml['show'] = $newArr;
				}

				$highestPercent = 0;

				foreach ($arrXml['show'] as $show) {

					if ($title == $show['name']) {
						$matchedTitle = $show;
						break;
					}

					// Get a match percentage based on our name and the name returned from tvr.
					$matchPercent = $this->checkMatch($title, $show['name'], self::MATCH_PROBABILITY);
					if ($matchPercent > $highestPercent) {
						$matchedTitle = $show;
						$highestPercent = $matchPercent;
					}

					// Check if there are any akas for this result and get a match percentage for them too.
					if (isset($show['akas']['aka'])) {
						if (is_array($show['akas']['aka'])) {
							// Multiple akas.
							foreach ($show['akas']['aka'] as $aka) {
								$matchPercent = $this->checkMatch($title, $aka, self::MATCH_PROBABILITY);
								if ($matchPercent > $highestPercent) {
									$matchedTitle = $show;
									$highestPercent = $matchPercent;
								}
							}
						} else {
							// One aka.
							$matchPercent = $this->checkMatch($title, $show['akas']['aka'], self::MATCH_PROBABILITY);
							if ($matchPercent > $highestPercent) {
								$show['akas']['aka'][] = $show['akas']['aka'];
								$matchedTitle = $show;
								$highestPercent = $matchPercent;
							}
						}
					}
				}
			} else {
				if ($this->echooutput) {
					echo $this->pdo->log->primary('Nothing returned from tvrage.');
				}
			}
		}
		return $this->formatShowInfo($matchedTitle);
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
		return [
			'type'      => (int)parent::TYPE_TV,
			'title'     => (string)$show['name'],
			'summary'   => (string)'',
			'started'   => (string)date('Y-m-d', strtotime($show['started'])),
			'publisher' => (string)$show['network'],
			'country'   => (string)$show['country'],
			'source'    => (int)parent::SOURCE_TVRAGE,
			'imdb'      => 0,
			'tvdb'      => 0,
			'trakt'     => 0,
			'tvrage'    => (int)$show['showid'],
			'tvmaze'    => 0,
			'tmdb'      => 0,
			'aliases'   => (!empty($show['akas']['aka']) ? (array)$show['akas']['aka'] : ''),
			'tvr'       => $show
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
		return false;
	}
}
