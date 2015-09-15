<?php
namespace nzedb;

use nzedb\utility\Misc;

/**
 * Class TvRage
 */
class TvRage extends TV
{
	const APIKEY = '7FwjZ8loweFcOhHfnU3E';
	const MATCH_PROBABILITY = 75;

	public $echooutput;
	public $rageqty;
	public $showInfoUrl         = 'http://www.tvrage.com/shows/id-';
	public $showQuickInfoURL    = 'http://services.tvrage.com/tools/quickinfo.php?show=';
	public $xmlFullSearchUrl    = 'http://services.tvrage.com/feeds/full_search.php?show=';
	public $xmlShowInfoUrl      = 'http://services.tvrage.com/feeds/showinfo.php?sid=';
	public $xmlFullShowInfoUrl  = 'http://services.tvrage.com/feeds/full_show_info.php?sid=';
	public $xmlEpisodeInfoUrl;
	public $xmlFullScheduleUrl  = 'http://services.tvrage.com/feeds/fullschedule.php?country=';

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->rageqty = ($this->pdo->getSetting('maxrageprocessed') != '') ? $this->pdo->getSetting('maxrageprocessed') : 75;
		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->xmlEpisodeInfoUrl    =  "http://services.tvrage.com/myfeeds/episodeinfo.php?key=" . TvRage::APIKEY;
	}

	/**
	 * Get rage info for a ID.
	 *
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function getByID($id)
	{
		return $this->pdo->queryOneRow(
						sprintf("
							SELECT *
							FROM tvrage_titles
							WHERE id = %d",
							$id
						)
		);
	}

	/**
	 * Get rage info for a rage ID.
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function getByRageID($id)
	{
		return $this->pdo->query(
					sprintf("
						SELECT *
						FROM tvrage_titles
						WHERE rageid = %d",
						$id
					)
		);
	}

	/**
	 * Get rage info for a title.
	 *
	 * @param $title
	 *
	 * @return bool
	 */
	public function getByTitle($title)
	{
		// Check if we already have an entry for this show.
		$res = $this->getByTitleQuery($title);
		if (isset($res['rageid'])) {
			return $res['rageid'];
		}

		$title2 = str_replace(' and ', ' & ', $title);
		if ($title != $title2) {
			$res = $this->getByTitleQuery($title2);
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
			$pieces = explode(' ', $title2);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4 . '%');
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
		}

		// Some words are spelled correctly 2 ways
		// example theatre and theater
		$title3 = str_replace('er', 're', $title);
		if ($title != $title3) {
			$res = $this->getByTitleQuery($title3);
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
			$pieces = explode(' ', $title3);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4);
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
		}

		// If there was not an exact title match, look for title with missing chars
		// example release name :Zorro 1990, tvrage name Zorro (1990)
		// Only search if the title contains more than one word to prevent incorrect matches
		$pieces = explode(' ', $title);
		if (count($pieces) > 1) {
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4);
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
		}

		return false;
	}

	private function getByTitleQuery($title)
	{
		if ($title) {
			return $this->pdo->queryOneRow(
						sprintf("
							SELECT rageid
							FROM tvrage_titles
							WHERE releasetitle = %s",
							$this->pdo->escapeString($title)
						)
			);
		}
	}

	private function getByTitleLikeQuery($title)
	{
		$string = '"\'"';
		if ($title) {
			return $this->pdo->queryOneRow(
						sprintf("
							SELECT rageid
							FROM tvrage_titles
							WHERE REPLACE(REPLACE(releasetitle, %s, ''), '!', '') LIKE %s",
							$string,
							$this->pdo->escapeString($title . '%')
						)
			);
		}
	}

	/**
	 * Get a country code for a country name.
	 *
	 * @param string $country
	 *
	 * @return mixed
	 */
	public function countryCode($country)
	{
		if (!is_array($country) && strlen($country) > 2) {
			$code = $this->pdo->queryOneRow(
							sprintf('
								SELECT code
								FROM countries
								WHERE name = %s',
								$this->pdo->escapeString($country)
							)
			);
			if (isset($code['code'])) {
				return $code['code'];
			}
		}
		return $country;
	}

	/**
	 * @param $rageid
	 * @param $releasename
	 * @param string $desc
	 * @param $genre
	 * @param $country
	 * @param $imgbytes
	 */
	public function add($rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{
		$releasename = str_replace(['.', '_'], [' ', ' '], $releasename);
		$country = $this->countryCode($country);

		if ($rageid != -2) {
			$ckid = $this->getById($rageid);
		} else {
			$ckid = $this->getByTitleQuery($releasename);
		}

		if (!isset($ckid['id'])) {
			$this->pdo->queryExec(
					sprintf('
						INSERT INTO tvrage_titles (rageid, releasetitle, description, genre, country, createddate, imgdata)
						VALUES (%s, %s, %s, %s, %s, NOW(), %s)',
						$rageid,
						$this->pdo->escapeString($releasename),
						$this->pdo->escapeString(substr($desc, 0, 10000)),
						$this->pdo->escapeString(substr($genre, 0, 64)),
						$this->pdo->escapeString($country),
						$this->pdo->escapeString($imgbytes)
					)
			);
		} else {
			$this->update($ckid['id'], $rageid, $releasename, $desc, $genre, $country, $imgbytes);
		}
	}

	public function update($id, $rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{
		$country = $this->countryCode($country);
		if ($imgbytes != '') {
			$imgbytes = ', imgdata = ' . $this->pdo->escapeString($imgbytes);
		}

		$this->pdo->queryExec(
				sprintf('
					UPDATE tvrage_titles
					SET rageid = %d, releasetitle = %s, description = %s, genre = %s, country = %s %s
					WHERE id = %d',
					$rageid,
					$this->pdo->escapeString($releasename),
					$this->pdo->escapeString(substr($desc, 0, 10000)),
					$this->pdo->escapeString($genre),
					$this->pdo->escapeString($country),
					$imgbytes,
					$id
				)
		);
	}

	public function delete($id)
	{
		return $this->pdo->queryExec(
					sprintf("
						DELETE
						FROM tvrage_titles
						WHERE id = %d",
						$id
					)
		);
	}

	public function fetchShowQuickInfo($show, array $options = [])
	{
		$defaults = ['exact' => '', 'episode' => ''];
		$options += $defaults;
		$ret = [];

		if (!$show) {
			return false;
		}

		$url = $this->showQuickInfoURL . urlencode($show);
		$url .= !empty($options['episode']) ? '&ep=' . urlencode($options['episode']) : '';
		$url .= !empty($options['exact']) ? '&exact=' . urlencode($options['exact']) : '';
		$fp = fopen($url, "r", false, stream_context_create(Misc::streamSslContextOptions()));
		if ($fp) {
			while (!feof($fp)) {
				$line = fgets($fp, 1024);
				list ($sec, $val) = explode('@', $line, 2);
				$val = trim($val);

				switch ($sec) {
					case 'Show ID':
						$ret['rageid'] = $val;
						break;
					case 'Show Name':
						$ret['name'] = $val;
						break;
					case 'Show URL':
						$ret['url'] = $val;
						break;
					case 'Premiered':
						$ret['premier'] = $val;
						break;
					case 'Country':
						$ret['country'] = $val;
						break;
					case 'Status':
						$ret['status'] = $val;
						break;
					case 'Classification':
						$ret['classification'] = $val;
						break;
					case 'Genres':
						$ret['genres'] = $val;
						break;
					case 'Network':
						$ret['network'] = $val;
						break;
					case 'Airtime':
						$ret['airtime'] = $val;
						break;
					case 'Latest Episode':
						list ($ep, $title, $airdate) = explode('^', $val);
						$ret['episode']['latest'] =
								$ep . ", \"" . $title . "\" aired on " . $airdate;
						break;
					case 'Next Episode':
						list ($ep, $title, $airdate) = explode('^', $val);
						$ret['episode']['next'] = $ep . ", \"" . $title . "\" airs on " . $airdate;
						break;
					case 'Episode Info':
						list ($ep, $title, $airdate) = explode('^', $val);
						$ret['episode']['info'] = $ep . ", \"" . $title . "\" aired on " . $airdate;
						break;
					case 'Episode URL':
						$ret['episode']['url'] = $val;
						break;
					case '':
						break;

					default:
						break;
				}
			}
			fclose($fp);

			return $ret;
		}
		return false;
	}

	public function getRange($start, $num, $ragename = "")
	{
		if ($start === false) {
			$limit = "";
		} else {
			$limit = "LIMIT " . $num . " OFFSET " . $start;
		}

		$rsql = '';
		if ($ragename != "") {
			$rsql .= sprintf("AND tvrage_titles.releasetitle LIKE %s ", $this->pdo->escapeString("%" . $ragename . "%"));
		}

		return $this->pdo->query(
					sprintf("
						SELECT id, rageid, releasetitle, description, createddate
						FROM tvrage_titles
						WHERE 1=1 %s
						ORDER BY rageid ASC %s",
						$rsql,
						$limit
					)
		);
	}

	public function getCount($ragename = "")
	{
		$rsql = '';
		if ($ragename != "") {
			$rsql .= sprintf("AND tvrage_titles.releasetitle LIKE %s ", $this->pdo->escapeString("%" . $ragename . "%"));
		}

		$res = $this->pdo->queryOneRow(
					sprintf("
						SELECT COUNT(id) AS num
						FROM tvrage_titles
						WHERE 1=1 %s",
						$rsql
					)
		);
		return $res["num"];
	}

	public function getCalendar($date = "")
	{
		if (!preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
			$date = date("Y-m-d");
		}
		$sql = $this->pdo->queryDirect(
					sprintf("
						SELECT *
						FROM tvrage_episodes
						WHERE DATE(airdate) = %s
						ORDER BY airdate ASC",
						$this->pdo->escapeString($date)
					)
		);
		return $sql;
	}

	public function getSeriesList($uid, $letter = "", $ragename = "")
	{
		$rsql = '';
		if ($letter != "") {
			if ($letter == '0-9') {
				$letter = '[0-9]';
			}

			$rsql .= sprintf("AND tvrage_titles.releasetitle REGEXP %s", $this->pdo->escapeString('^' . $letter));
		}
		$tsql = '';
		if ($ragename != '') {
			$tsql .= sprintf("AND tvrage_titles.releasetitle LIKE %s", $this->pdo->escapeString("%" . $ragename . "%"));
		}

		return $this->pdo->query(
			sprintf("
				SELECT tvrage_titles.id, tvrage_titles.rageid, tvrage_titles.releasetitle, tvrage_titles.genre, tvrage_titles.country,
					tvrage_titles.createddate, tvrage_titles.prevdate, tvrage_titles.nextdate,
					user_series.id AS userseriesid
				FROM tvrage_titles
				LEFT OUTER JOIN user_series ON user_series.user_id = %d
					AND user_series.rageid = tvrage_titles.rageid
				WHERE tvrage_titles.rageid IN (
								SELECT DISTINCT rageid
								FROM releases
								WHERE %s
								AND rageid > 0)
				AND tvrage_titles.rageid > 0 %s %s
				GROUP BY tvrage_titles.rageid
				ORDER BY tvrage_titles.releasetitle ASC",
				$this->catWhere,
				$uid,
				$rsql,
				$tsql
			)
		);
	}

	public function updateSchedule()
	{
		$countries = $this->pdo->query("
						SELECT DISTINCT(country) AS country
						FROM tvrage_titles
						WHERE country != ''"
		);
		$showsindb = $this->pdo->query("
						SELECT DISTINCT(rageid) AS rageid
						FROM tvrage_titles"
		);
		$showarray = [];
		foreach ($showsindb as $show) {
			$showarray[] = $show['rageid'];
		}
		foreach ($countries as $country) {
			if ($this->echooutput) {
				echo $this->pdo->log->headerOver('Updating schedule for: ') . $this->pdo->log->primary($country['country']);
			}

			$sched = Misc::getURL(['url' => $this->xmlFullScheduleUrl . $country['country']]);
			if ($sched !== false && ($xml = @simplexml_load_string($sched))) {
				$tzOffset = 60 * 60 * 6;
				$yesterday = strtotime("-1 day") - $tzOffset;
				$xmlSchedule = [];

				foreach ($xml->DAY as $sDay) {
					$currDay = strtotime($sDay['attr']);
					foreach ($sDay as $sTime) {
						$currTime = (string)$sTime['attr'];
						foreach ($sTime as $sShow) {
							$currShowName = (string)$sShow['name'];
							$currShowId = (string)$sShow->sid;
							$day_time = strtotime($sDay['attr'] . ' ' . $currTime);
							$tag = ($currDay < $yesterday) ? 'prev' : 'next';
							if ($tag == 'prev' || ($tag == 'next' && !isset($xmlSchedule[$currShowId]['next']))) {
								$xmlSchedule[$currShowId][$tag] = [
												'name'     => $currShowName,
												'day'      => $currDay,
												'time'     => $currTime,
												'day_time' => $day_time,
												'day_date' => date("Y-m-d H:i:s", $day_time),
												'title'    => html_entity_decode((string)$sShow->title, ENT_QUOTES, 'UTF-8'), 													'episode'  => html_entity_decode((string)$sShow->ep, ENT_QUOTES, 'UTF-8')
												];
								$xmlSchedule[$currShowId]['showname'] = $currShowName;
							}

							// Only add it here, no point adding it to tvrage aswell that will automatically happen when an ep gets posted.
							if ($sShow->ep == "01x01") {
								$showarray[] = $sShow->sid;
							}

							// Only stick current shows and new shows in there.
							if (in_array($currShowId, $showarray)) {
								$this->pdo->queryExec(
										sprintf("
											INSERT INTO tvrage_episodes (rageid, showtitle, fullep, airdate, link, eptitle)
											VALUES (%d, %s, %s, %s, %s, %s)
											ON DUPLICATE KEY UPDATE
												showtitle = %2\$s, airdate = %4\$s, link = %5\$s ,eptitle = %6\$s",
											$sShow->sid,
											$this->pdo->escapeString($currShowName),
											$this->pdo->escapeString($sShow->ep),
											$this->pdo->escapeString(date("Y-m-d H:i:s", $day_time)),
											$this->pdo->escapeString($sShow->link),
											$this->pdo->escapeString($sShow->title),
											$this->pdo->escapeString(date("Y-m-d H:i:s", $day_time))
										)
								);
							}
						}
					}
				}
				// Update series info.
				foreach ($xmlSchedule as $showId => $epInfo) {
					$res = $this->pdo->query(sprintf("SELECT * FROM tvrage_titles WHERE rageid = %d", $showId));
					if (sizeof($res) > 0) {
						foreach ($res as $arr) {
							$prev_ep = $next_ep = "";
							$query = [];

							// Previous episode.
							if (isset($epInfo['prev']) && $epInfo['prev']['episode'] != '') {
								$prev_ep = $epInfo['prev']['episode'] . ', "' . $epInfo['prev']['title'] . '"';
								$query[] = sprintf("prevdate = %s, previnfo = %s",
										$this->pdo->from_unixtime($epInfo['prev']['day_time']),
										$this->pdo->escapeString($prev_ep)
								);
							}

							// Next episode.
							if (isset($epInfo['next']) && $epInfo['next']['episode'] != '') {
								if ($prev_ep == "" && $arr['nextinfo'] != '' && $epInfo['next']['day_time'] > strtotime($arr["nextdate"])
									&& strtotime(date('Y-m-d', strtotime($arr["nextdate"]))) < $yesterday) {
									$this->pdo->queryExec(
											sprintf("
												UPDATE tvrage_titles
												SET prevdate = nextdate, previnfo = nextinfo
												WHERE id = %d",
												$arr['id']
											)
									);
									$prev_ep = "SWAPPED with: " . $arr['nextinfo'] . " - " . date("r", strtotime($arr["nextdate"]));
								}
								$next_ep = $epInfo['next']['episode'] . ', "' . $epInfo['next']['title'] . '"';
								$query[] = sprintf("nextdate = %s, nextinfo = %s",
										$this->pdo->from_unixtime($epInfo['next']['day_time']),
										$this->pdo->escapeString($next_ep)
								);
							} else {
								$query[] = "nextdate = NULL, nextinfo = NULL";
							}

							// Output.
							if ($this->echooutput) {
								echo $this->pdo->log->primary($epInfo['showname'] . " (" . $showId . "):");
								if (isset($epInfo['prev']['day_time'])) {
									echo 	$this->pdo->log->headerOver("Prev EP: ") .
										$this->pdo->log->primary("{$prev_ep} - " .
											date("m/d/Y H:i T", $epInfo['prev']['day_time'])
										);
								}
								if (isset($epInfo['next']['day_time'])) {
									echo 	$this->pdo->log->headerOver("Next EP: ") .
										$this->pdo->log->primary("{$next_ep} - " .
											date("m/d/Y H:i T", $epInfo['next']['day_time'])
										);
								}
								echo "\n";
							}

							// Update info.
							if (count($query) > 0) {
								$sqlQry = join(", ", $query);
								$this->pdo->queryExec(
										sprintf("
											UPDATE tvrage_titles
											SET %s
											WHERE id = %d",
											$sqlQry,
											$arr['id']
										)
								);
							}
						}
					}
				}
			} else {
				// No response from tvrage.
				if ($this->echooutput) {
					echo $this->pdo->log->info("Schedule not found.");
				}
			}
		}
		if ($this->echooutput) {
			echo $this->pdo->log->primary("Updated the TVRage schedule succesfully.");
		}
	}

	public function getEpisodeInfo($rageid, $series, $episode)
	{
		$result = ['title' => '', 'airdate' => ''];

		$series = str_ireplace("s", "", $series);
		$episode = str_ireplace("e", "", $episode);
		$xml = Misc::getUrl(['url' => $this->xmlEpisodeInfoUrl . "&sid=" . $rageid . "&ep=" . $series . "x" . $episode]);
		if ($xml !== false) {
			if (preg_match('/no show found/i', $xml)) {
				return false;
			}

			$xmlObj = @simplexml_load_string($xml);
			$arrXml = Misc::objectsIntoArray($xmlObj);
			if (is_array($arrXml)) {
				if (isset($arrXml['episode']['airdate']) && $arrXml['episode']['airdate'] != '0000-00-00') {
					$result['airdate'] = $arrXml['episode']['airdate'];
				}
				if (isset($arrXml['episode']['title'])) {
					$result['title'] = $arrXml['episode']['title'];
				}

				return $result;
			}
			return false;
		}
		return false;
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

	/**
	 * @param string $rageid
	 *
	 * @return array|bool|mixed
	 */
	public function getRageInfoFromService($rageid)
	{
		$result = ['genres' => '', 'country' => '', 'showid' => $rageid];
		// Full search gives us the akas.
		$xml = Misc::getUrl(['url' => $this->xmlShowInfoUrl . $rageid]);
		if ($xml !== false) {
			$arrXml = Misc::objectsIntoArray(simplexml_load_string($xml));
			if (is_array($arrXml)) {
				$result['genres'] = (isset($arrXml['genres'])) ? $arrXml['genres'] : '';
				$result['country'] = (isset($arrXml['origin_country'])) ? $arrXml['origin_country'] : '';
				$result = $this->countryCode($result);
				return $result;
			}
			return false;
		}
		return false;
	}

	//
	/**
	 * Convert 2012-24-07 to 2012-07-24, there is probably a better way
	 *
	 * This shouldn't ever happen as I've never heard of a date starting with year being followed by day value.
	 * Could this be a mistake? i.e. trying to solve the mm-dd-yyyy/dd-mm-yyyy confusion into a yyyy-mm-dd?
	 *
	 * @param string $date
	 *
	 * @return string
	 */
	public function checkDate($date)
	{
		if (!empty($date)) {
			$chk = explode(" ", $date);
			$chkd = explode("-", $chk[0]);
			if ($chkd[1] > 12) {
				$date = date('Y-m-d H:i:s', strtotime($chkd[1] . " " . $chkd[2] . " " . $chkd[0]));
			}
		} else {
			$date = null;
		}

		return $date;
	}

	public function updateEpInfo($show, $relid)
	{
		if ($this->echooutput) {
			echo 	$this->pdo->log->headerOver("Updating Episode: ") .
				$this->pdo->log->primary($show['cleanname'] . " " .
					$show['seriesfull'] . (($show['year'] != '') ? ' ' .
					$show['year'] : '') . (($show['country'] != '') ? ' [' .
					$show['country'] . ']' : '')
				);
		}

		$tvairdate = (isset($show['airdate']) && !empty($show['airdate'])) ? $this->pdo->escapeString($this->checkDate($show['airdate'])) : "NULL";
		$this->pdo->queryExec(
				sprintf("
					UPDATE releases
					SET seriesfull = %s, season = %s, episode = %s, tvairdate = %s
					WHERE %s AND id = %d",
					$this->pdo->escapeString($show['seriesfull']),
					$this->pdo->escapeString($show['season']),
					$this->pdo->escapeString($show['episode']),
					$tvairdate,
					$this->catWhere,
					$relid
				)
		);
	}

	public function updateRageInfo($rageid, $show, $tvrShow, $relid)
	{
		// Try and get the episode specific info from tvrage.
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false) {
			$tvairdate = (!empty($epinfo['airdate'])) ? $this->pdo->escapeString($epinfo['airdate']) : "NULL";
			$tvtitle = (!empty($epinfo['title'])) ? $this->pdo->escapeString($epinfo['title']) : "NULL";

			$this->pdo->queryExec(
					sprintf("
						UPDATE releases
						SET tvtitle = %s, tvairdate = %s, rageid = %d
						WHERE id = %d",
						$this->pdo->escapeString(trim($tvtitle)),
						$tvairdate,
						$tvrShow['showid'],
						$relid
					)
			);
		} else {
			$this->pdo->queryExec(
					sprintf("
						UPDATE releases
						SET rageid = %d
						WHERE id = %d",
						$tvrShow['showid'],
						$relid
					)
			);
		}

		$genre = '';
		if (isset($tvrShow['genres']) && is_array($tvrShow['genres']) && !empty($tvrShow['genres'])) {
			if (is_array($tvrShow['genres']['genre'])) {
				$genre = implode('|', $tvrShow['genres']['genre']);
			} else {
				$genre = $tvrShow['genres']['genre'];
			}
		}

		$country = '';
		if (isset($tvrShow['country']) && !empty($tvrShow['country'])) {
			$country = $this->countryCode($tvrShow['country']);
		}

		$rInfo = $this->getRageInfoFromPage($rageid);
		$desc = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc'])) {
			$desc = $rInfo['desc'];
		}

		$imgbytes = '';
		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
			$img = Misc::getUrl(['url' => $rInfo['imgurl']]);
			if ($img !== false) {
				$im = @imagecreatefromstring($img);
				if ($im !== false) {
					$imgbytes = $img;
				}
			}
		}
		$this->add($rageid, $show['cleanname'], $desc, $genre, $country, $imgbytes);
	}

	public function updateRageInfoTrakt($rageid, $show, $traktArray, $relid)
	{
		// Try and get the episode specific info from tvrage.
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false) {
			$tvairdate = (!empty($epinfo['airdate'])) ? $this->pdo->escapeString($epinfo['airdate']) : "NULL";
			$tvtitle = (!empty($epinfo['title'])) ? $this->pdo->escapeString($epinfo['title']) : "NULL";
			$this->pdo->queryExec(
					sprintf("
						UPDATE releases
						SET tvtitle = %s, tvairdate = %s, rageid = %d
						WHERE %s
						AND id = %d",
						$this->pdo->escapeString(trim($tvtitle)),
						$tvairdate,
						$traktArray['ids']['tvrage'],
						$this->catWhere,
						$relid
					)
			);
		} else {
			$this->pdo->queryExec(
					sprintf("
						UPDATE releases
						SET rageid = %d
						WHERE %s
						AND id = %d",
						$traktArray['ids']['tvrage'],
						$this->catWhere,
						$relid
					)
			);
		}

		$genre = $country = '';

		$rInfo = $this->getRageInfoFromPage($rageid);
		$desc = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc'])) {
			$desc = $rInfo['desc'];
		}

		$imgbytes = '';
		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
			$img = Misc::getUrl(['url' => $rInfo['imgurl']]);
			if ($img !== false) {
				$im = @imagecreatefromstring($img);
				if ($im !== false) {
					$imgbytes = $img;
				}
			}
		}

		$this->add($rageid, $show['cleanname'], $desc, $genre, $country, $imgbytes);
	}

	public function processTvReleases($groupID = '', $guidChar = '', $lookupTvRage = 1, $local = false)
	{
		$ret = 0;
		if ($lookupTvRage == 0) {
			return $ret;
		}
		$trakt = new TraktTv(['Settings' => $this->pdo]);

		// Get all releases without a rageid which are in a tv category.

		$res = $this->pdo->query(
			sprintf("
				SELECT r.searchname, r.id
				FROM releases r
				WHERE r.nzbstatus = 1
				AND r.rageid = -1
				AND r.size > 1048576
				AND %s
				%s %s %s
				ORDER BY r.postdate DESC
				LIMIT %d",
				$this->catWhere,
				($groupID === '' ? '' : 'AND r.group_id = ' . $groupID),
				($guidChar === '' ? '' : 'AND r.guid ' . $this->pdo->likeString($guidChar, false, true)),
				($lookupTvRage == 2 ? 'AND r.isrenamed = 1' : ''),
				$this->rageqty
			)
		);
		$tvcount = count($res);

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TV for " . $tvcount . " release(s).");
		}

		foreach ($res as $arr) {
			$show = $this->parseNameEpSeason($arr['searchname']);
			if (is_array($show) && $show['name'] != '') {
				// Update release with season, ep, and airdate info (if available) from releasetitle.
				$this->updateEpInfo($show, $arr['id']);

				// Find the rageID.
				$id = $this->getByTitle($show['cleanname']);

				// Force local lookup only
				if ($local == true) {
					$lookupTvRage = false;
				}

				if ($id === false && $lookupTvRage) {
					// If it doesnt exist locally and lookups are allowed lets try to get it.
					if ($this->echooutput) {
						echo 	$this->pdo->log->primaryOver("TVRage ID for ") .
							$this->pdo->log->headerOver($show['cleanname']) .
							$this->pdo->log->primary(" not found in local db, checking web.");
					}

					$tvrShow = $this->getRageMatch($show);
					if ($tvrShow !== false && is_array($tvrShow)) {
						// Get all tv info and add show.
						$this->updateRageInfo($tvrShow['showid'], $show, $tvrShow, $arr['id']);
					} else if ($tvrShow === false) {
						// If tvrage fails, try trakt.
						$traktArray = $trakt->episodeSummary($show['name'], $show['season'], $show['episode']);
						if ($traktArray !== false) {
							if (isset($traktArray['ids']['tvrage']) && $traktArray['ids']['tvrage'] !== 0) {
								if ($this->echooutput) {
									echo $this->pdo->log->primary('Found TVRage ID on trakt:' . $traktArray['ids']['tvrage']);
								}
								$this->updateRageInfoTrakt($traktArray['ids']['tvrage'], $show, $traktArray, $arr['id']);
							}
							// No match, add to tvrage with rageID = -2 and $show['cleanname'] title only.
							else {
								$this->add(-2, $show['cleanname'], '', '', '', '');
							}
						}
						// No match, add to tvrage with rageID = -2 and $show['cleanname'] title only.
						else {
							$this->add(-2, $show['cleanname'], '', '', '', '');
						}
					}
				} else if ($id > 0) {
					$tvtitle = "NULL";
					$tvairdate = (isset($show['airdate']) && !empty($show['airdate']))
							? $this->pdo->escapeString($this->checkDate($show['airdate']))
							: "NULL";

					if ($lookupTvRage) {
						$epinfo = $this->getEpisodeInfo($id, $show['season'], $show['episode']);
						if ($epinfo !== false) {
							if (isset($epinfo['airdate'])) {
								$tvairdate = $this->pdo->escapeString($this->checkDate($epinfo['airdate']));
							}

							if (!empty($epinfo['title'])) {
								$tvtitle = $this->pdo->escapeString(trim($epinfo['title']));
							}
						}
					}
					if ($tvairdate == "NULL") {
						$this->pdo->queryExec(
								sprintf('
									UPDATE releases
									SET tvtitle = %s, rageid = %d
									WHERE %s
									AND id = %d',
									$tvtitle,
									$id,
									$this->catWhere,
									$arr['id']
								)
						);
					} else {
						$this->pdo->queryExec(
								sprintf('
									UPDATE releases
									SET tvtitle = %s, tvairdate = %s, rageid = %d
									WHERE %s
									AND id = %d',
									$tvtitle,
									$tvairdate,
									$id,
									$this->catWhere,
									$arr['id']
								)
						);
					}
					// Cant find rageid, so set rageid to n/a.
				} else {
					$this->setRageNotFound($arr['id']);
				}
				// Not a tv episode, so set rageid to n/a.
			} else {
				$this->setRageNotFound($arr['id']);
			}
			$ret++;
		}
		return $ret;
	}

	private function setRageNotFound($Id)
	{
		if ($Id) {
			$this->pdo->queryExec(
					sprintf('
						UPDATE releases
						SET rageid = -2
						WHERE %s
						AND id = %d',
						$this->catWhere,
						$Id
					)
			);
		}
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
											'genres' => $arr['genres'],
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
												'genres' => $arr['genres'],
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
														'genres' => $arr['genres'],
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
													'genres' => $arr['genres'],
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

				if ($this->echooutput) {
					echo $this->pdo->log->primary('No match found on TVRage trying Trakt.');
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
