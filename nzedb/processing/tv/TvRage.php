<?php
namespace nzedb\processing\tv;

use nzedb\utility\Misc;
use nzedb\ReleaseImage;

/**
 * Class TvRage
 */
class TvRage extends TV
{
	const APIKEY = '7FwjZ8loweFcOhHfnU3E';
	const MATCH_PROBABILITY = 75;

	public $rageqty;
	public $showInfoUrl         = 'http://www.tvrage.com/shows/id-';
	public $showQuickInfoURL    = 'http://services.tvrage.com/tools/quickinfo.php?show=';
	public $xmlFullSearchUrl    = 'http://services.tvrage.com/feeds/full_search.php?show=';
	public $xmlShowInfoUrl      = 'http://services.tvrage.com/feeds/showinfo.php?sid=';
	public $xmlFullShowInfoUrl  = 'http://services.tvrage.com/feeds/full_show_info.php?sid=';
	public $xmlEpisodeInfoUrl;
	public $imgSavePath;

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->rageqty = ($this->pdo->getSetting('maxrageprocessed') != '') ? $this->pdo->getSetting('maxrageprocessed') : 75;
		$this->xmlEpisodeInfoUrl    =  "http://services.tvrage.com/myfeeds/episodeinfo.php?key=" . TvRage::APIKEY;
		$this->imgSavePath = nZEDb_COVERS . 'tvrage' . DS;
	}

	public function processTvRage($groupID = '', $guidChar = '', $lookupSetting = 1, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $local, parent::PROCESS_TVRAGE);

		$tvcount = $res->rowCount();

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TV Rage lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $arr) {
				$show = $this->parseNameEpSeason($arr['searchname']);
				if (is_array($show) && $show['name'] != '') {
					// Find the Video ID if it already exists by checking the title.
					$video = $this->getByTitle($show['cleanname']);

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					}

					if ($video === false && $lookupSetting) {
						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($show['cleanname']) .
								 $this->pdo->log->primary(" not found in local db, checking web.");
						}
						$tvrShow = $this->getRageMatch($show);
						if ($tvrShow !== false && is_array($tvrShow)) {
							// Get all tv info and add show.
							$this->updateRageInfo($tvrShow['showid'], $tvrShow);
						} else {
							$this->setVideoNotFound(parent::PROCESS_TVMAZE, $arr['id']);
						}
					} else if ($video > 0) {
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($show['cleanname']) .
								 $this->pdo->log->primary(" found in local db, setting tvrage ID and attempting episode lookup.");
						}
						$episodeId = $this->getBySeasonEp($video['id'],  $show['season'], $show['episode']);
						if ($episodeId === false) {
							$epinfo = $this->getEpisodeInfo($video['tvrage'], $show['season'], $show['episode']);
							if ($epinfo !== false && isset($epinfo['airdate']) && !empty($epinfo['title'])) {
								$tvairdate = $this->pdo->escapeString($this->checkDate($epinfo['airdate']));
								$tvtitle = $this->pdo->escapeString(trim($epinfo['title']));
								$seComplete = 'S' . $show['season'] . 'E' . $show['episode'];
								$episodeId = $this->addEpisode($video['id'], $show['season'], $show['episode'], $seComplete, $tvairdate, $tvtitle, '');
							}
						}
						$this->setVideoIdFound($video, $arr['id'], $episodeId);
					// Cant find videos_id, so set tv_episodes_id to PROCESS_TVMAZE.
					} else {
						$this->setVideoNotFound(parent::PROCESS_TVMAZE, $arr['id']);
					}
				// Not a tv episode, so set videos_id to n/a.
				} else {
					$this->setVideoNotFound(parent::PROCESS_TVMAZE, $arr['id']);
				}
			}
		}
	}

	public function updateRageInfo($rageid, $tvrShow)
	{
		$hasCover = 0;
		$country = '';

		if (isset($tvrShow['country']) && !empty($tvrShow['country'])) {
			$country = $this->countryCode($tvrShow['country']);
		}

		$rInfo = $this->getRageInfoFromPage($rageid);
		$summary = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc'])) {
			$summary = $rInfo['desc'];
		}

		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
			$hasCover = (new ReleaseImage($this->pdo))->saveImage($rageid, $rInfo['imgurl'], $this->imgSavePath, '', '');
		}
		$this->add('tvrage', $rageid, $tvrShow['title'], $summary, $country, $tvrShow['publisher'], $hasCover, parent::SOURCE_TVRAGE);
	}

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
	 * @param string $rageid
	 *
	 * @return array|bool|mixed
	 */
	public function getRageInfoFromService($rageid)
	{
		$result = false;
		// Full search gives us the akas.
		$xml = Misc::getUrl(['url' => $this->xmlShowInfoUrl . $rageid]);
		if ($xml !== false) {
			$arrXml = Misc::objectsIntoArray(simplexml_load_string($xml));
			if (is_array($arrXml)) {
				$result = ['showid' => $rageid];
				$result['country'] = (isset($arrXml['origin_country'])) ? $arrXml['origin_country'] : '';
				$result['firstaired'] = (isset($arrXml['startdate'])) ? date('m-d-Y', strtotime($arrXml['startdate'])) : '';
				$result = $this->countryCode($result);
			}
		}
		return $result;
	}

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

	public function getRageMatch($showInfo)
	{
		$title = $showInfo['cleanname'];
		// Full search gives us the akas.
		$xml = Misc::getUrl(['url' => $this->xmlFullSearchUrl . urlencode(strtolower($title))]);
		if ($xml !== false) {
			$arrXml = @Misc::objectsIntoArray(simplexml_load_string($xml));
			if (isset($arrXml['show']) && is_array($arrXml)) {
				// We got a valid xml response
				$titleMatches = $urlMatches = $akaMatches = [];

				if (isset($arrXml['show']['showid'])) {
					// We got exactly 1 match so lets convert it to an array so we can use it in the logic below.
					$newArr = [];
					$newArr[] = $arrXml['show'];
					unset($arrXml);
					$arrXml['show'] = $newArr;
				}

				foreach ($arrXml['show'] as $arr) {
					$tvrlink = '';

					// Get a match percentage based on our name and the name returned from tvr.
					$titlepct = $this->checkMatch($title, $arr['name']);
					if ($titlepct !== false) {
						$titleMatches[$titlepct][] = 	[
											'title' => $arr['name'],
											'showid' => $arr['showid'],
											'country' => $this->countryCode($arr['country']),
											'publisher' => $arr['network'],
											'started' => date('m-d-Y', strtotime($arr['started'])),
											'tvr' => $arr
										];
					}

					// Get a match percentage based on our name and the url returned from tvr.
					if (isset($arr['link']) && preg_match('/tvrage\.com\/((?!shows)[^\/]*)$/i', $arr['link'], $tvrlink)) {
						$urltitle = str_replace('_', ' ', $tvrlink[1]);
						$urlpct = $this->checkMatch($title, $urltitle);
						if ($urlpct !== false) {
							$urlMatches[$urlpct][] = 	[
												'title' => $urltitle,
												'showid' => $arr['showid'],
												'country' => $this->countryCode($arr['country']),
												'publisher' => $arr['network'],
												'started' => date('m-d-Y', strtotime($arr['started'])),
												'tvr' => $arr
											];
						}
					}

					// Check if there are any akas for this result and get a match percentage for them too.
					if (isset($arr['akas']['aka'])) {
						if (is_array($arr['akas']['aka'])) {
							// Multuple akas.
							foreach ($arr['akas']['aka'] as $aka) {
								$akapct = $this->checkMatch($title, $aka);
								if ($akapct !== false) {
									$akaMatches[$akapct][] = 	[
														'title' => $aka,
														'showid' => $arr['showid'],
														'country' => $this->countryCode($arr['country']),
														'publisher' => $arr['network'],
														'started' => date('m-d-Y', strtotime($arr['started'])),
														'tvr' => $arr
													];
								}
							}
						} else {
							// One aka.
							$akapct = $this->checkMatch($title, $arr['akas']['aka']);
							if ($akapct !== false) {
								$akaMatches[$akapct][] = 	[
													'title' => $arr['akas']['aka'],
													'showid' => $arr['showid'],
													'country' => $this->countryCode($arr['country']),
													'publisher' => $arr['network'],
													'started' => date('m-d-Y', strtotime($arr['started'])),
													'tvr' => $arr
												];
							}
						}
					}
				}

				// Reverse sort our matches so highest matches are first.
				krsort($titleMatches);
				krsort($urlMatches);
				krsort($akaMatches);

				// Look for 100% title matches first.
				if (isset($titleMatches[100])) {
					if ($this->echooutput) {
						echo $this->pdo->log->primary('Found 100% match: "' . $titleMatches[100][0]['title'] . '"');
					}
					return $titleMatches[100][0];
				}

				// Look for 100% url matches next.
				if (isset($urlMatches[100])) {
					if ($this->echooutput) {
						echo $this->pdo->log->primary('Found 100% url match: "' . $urlMatches[100][0]['title'] . '"');
					}
					return $urlMatches[100][0];
				}

				// Look for 100% aka matches next.
				if (isset($akaMatches[100])) {
					if ($this->echooutput) {
						echo $this->pdo->log->primary('Found 100% aka match: "' . $akaMatches[100][0]['title'] . '"');
					}
					return $akaMatches[100][0];
				}

				// No 100% matches, loop through what we got and if our next closest match is more than TvRage::MATCH_PROBABILITY % of the title lets take it.
				foreach ($titleMatches as $mk => $mv) {
					// Since its not 100 match if we have country info lets use that to make sure we get the right show.
					if (isset($showInfo['country']) && !empty($showInfo['country']) && !empty($mv[0]['country'])) {
						if (strtolower($showInfo['country']) != strtolower($mv[0]['country'])) {
							continue;
						}
					}

					if ($this->echooutput) {
						echo $this->pdo->log->primary('Found ' . $mk . '% match: "' . $titleMatches[$mk][0]['title'] . '"');
					}
					return $titleMatches[$mk][0];
				}

				// Same as above but for akas.
				foreach ($akaMatches as $ak => $av) {
					if (isset($showInfo['country']) && !empty($showInfo['country']) && !empty($av[0]['country'])) {
						if (strtolower($showInfo['country']) != strtolower($av[0]['country'])) {
							continue;
						}
					}

					if ($this->echooutput) {
						echo $this->pdo->log->primary('Found ' . $ak . '% aka match: "' . $akaMatches[$ak][0]['title'] . '"');
					}
					return $akaMatches[$ak][0];
				}

				return false;
			} else {
				if ($this->echooutput) {
					echo $this->pdo->log->primary('Nothing returned from tvrage.');
				}
				return false;
			}
		} else {
			return -1;
		}
	}
}
