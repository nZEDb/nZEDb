<?php

require_once nZEDb_LIB . 'Util.php';

class TvRage
{

	const APIKEY = '7FwjZ8loweFcOhHfnU3E';
	const MATCH_PROBABILITY = 75;

	function __construct($echooutput = false)
	{
		$this->db = new DB();
		$s = new Sites();
		$site = $s->get();
		$this->rageqty = (!empty($site->maxrageprocessed)) ? $site->maxrageprocessed : 75;
		$this->echooutput = $echooutput;
		$this->c = new ColorCLI();

		$this->xmlFullSearchUrl = "http://services.tvrage.com/feeds/full_search.php?show=";
		$this->xmlShowInfoUrl = "http://services.tvrage.com/feeds/showinfo.php?sid=";
		$this->xmlFullShowInfoUrl = "http://services.tvrage.com/feeds/full_show_info.php?sid=";
		$this->xmlEpisodeInfoUrl = "http://services.tvrage.com/myfeeds/episodeinfo.php?key=" . TvRage::APIKEY;
		$this->xmlFullScheduleUrl = "http://services.tvrage.com/feeds/fullschedule.php?country=";

		$this->showInfoUrl = "http://www.tvrage.com/shows/id-";
	}

	public function getByID($id)
	{
		return $this->db->queryOneRow(sprintf("SELECT * FROM tvrage WHERE id = %d", $id));
	}

	public function getByRageID($id)
	{
		return $this->db->query(sprintf("SELECT * FROM tvrage WHERE rageid = %d", $id));
	}

	public function getByTitle($title)
	{
		// Check if we already have an entry for this show.
		$res = $this->db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE LOWER(releasetitle) = LOWER(%s)", $this->db->escapeString($title)));
		if (isset($res['rageid'])) {
			return $res['rageid'];
		}

		$title2 = str_replace(' and ', ' & ', $title);
		if ($title != $title2) {
			$res = $this->db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE LOWER(releasetitle) = LOWER(%s)", $this->db->escapeString($title2)));
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
			$pieces = explode(' ', $title2);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(array("'", "!"), "", $piece) . '%';
			}
			$res = $this->db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE replace(replace(releasetitle, \"'\", ''), '!', '') LIKE %s", $this->db->escapeString($title4)));
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
		}

		// Some words are spelled correctly 2 ways
		// example theatre and theater
		$title3 = str_replace('er', 're', $title);
		if ($title != $title3) {
			$res = $this->db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE LOWER(releasetitle) = LOWER(%s)", $this->db->escapeString($title3)));
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
			$pieces = explode(' ', $title3);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(array("'", "!"), "", $piece) . '%';
			}
			$res = $this->db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE replace(replace(releasetitle, \"'\", ''), '!', '') LIKE %s", $this->db->escapeString($title4)));
			if (isset($res['rageid'])) {
				return $res['rageid'];
			}
		}

		// If there was not an exact title match, look for title with missing chars
		// example release name :Zorro 1990, tvrage name Zorro (1990)
		$pieces = explode(' ', $title);
		$title4 = '%';
		foreach ($pieces as $piece) {
			$title4 .= str_replace(array("'", "!"), "", $piece) . '%';
		}
		$res = $this->db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE replace(replace(releasetitle, \"'\", ''), '!', '') LIKE %s", $this->db->escapeString($title4)));
		if (isset($res['rageid'])) {
			return $res['rageid'];
		}

		return false;
	}

	public function countryCode($country)
	{
		if (!is_array($country) && strlen($country) > 2) {
			$code = $this->db->queryOneRow('SELECT code FROM country WHERE LOWER(name) = LOWER(' . $this->db->escapeString($country) . ')');
			if (isset($code['code'])) {
				return $code['code'];
			}
		}
		return $country;
	}

	public function add($rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{
		$releasename = str_replace(array('.', '_'), array(' ', ' '), $releasename);
		$country = $this->countryCode($country);

		if ($rageid != -2) {
			$ckid = $this->db->queryOneRow('SELECT id FROM tvrage WHERE rageid = ' . $rageid);
		} else {
			$ckid = $this->db->queryOneRow('SELECT id FROM tvrage WHERE releasetitle = ' . $this->db->escapeString($releasename));
		}

		if ($this->db->dbSystem() == 'mysql') {
			if (!isset($ckid['id']) || $rageid == -2) {
				$this->db->queryExec(sprintf('INSERT INTO tvrage (rageid, releasetitle, description, genre, country, createddate, imgdata) VALUES (%s, %s, %s, %s, %s, NOW(), %s)', $rageid, $this->db->escapeString($releasename), $this->db->escapeString(substr($desc, 0, 10000)), $this->db->escapeString(substr($genre, 0, 64)), $this->db->escapeString($country), $this->db->escapeString($imgbytes)));
			} else {
				$this->db->queryExec(sprintf('UPDATE tvrage SET releasetitle = %s, description = %s, genre = %s, country = %s, createddate = NOW(), imgdata = %s WHERE rageid = %d', $this->db->escapeString($releasename), $this->db->escapeString(substr($desc, 0, 10000)), $this->db->escapeString(substr($genre, 0, 64)), $this->db->escapeString($country), $this->db->escapeString($imgbytes), $rageid));
			}
		} else {
			if (!isset($ckid['id']) || $rageid == -2) {
				$id = $this->db->queryInsert(sprintf('INSERT INTO tvrage (rageid, releasetitle, description, genre, country, createddate) VALUES (%d, %s, %s, %s, %s, NOW())', $rageid, $this->db->escapeString($releasename), $this->db->escapeString(substr($desc, 0, 10000)), $this->db->escapeString(substr($genre, 0, 64)), $this->db->escapeString($country)));
			} else {
				$id = $ckid['id'];
				$this->db->queryExec(sprintf('UPDATE tvrage SET releasetitle = %s, description = %s, genre = %s, country = %s, createddate = NOW() WHERE rageid = %d', $this->db->escapeString($releasename), $this->db->escapeString(substr($desc, 0, 10000)), $this->db->escapeString(substr($genre, 0, 64)), $this->db->escapeString($country), $rageid));
			}
			if ($imgbytes != '') {
				$path = nZEDb_WWW . 'covers/tvrage/' . $id . '.jpg';
				if (file_exists($path)) {
					unlink($path);
				}
				$check = file_put_contents($path, $imgbytes);
				if ($check !== false) {
					$this->db->Exec("UPDATE tvrage SET imgdata = 'x' WHERE id = " . $id);
					chmod($path, 0755);
				}
			}
		}
	}

	public function update($id, $rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{
		$country = $this->countryCode($country);
		if ($this->db->dbSystem() == 'mysql') {
			if ($imgbytes != '') {
				$imgbytes = ', imgdata = ' . $this->db->escapeString($imgbytes);
			}

			$this->db->queryExec(sprintf('UPDATE tvrage SET rageid = %d, releasetitle = %s, description = %s, genre = %s, country = %s %s WHERE id = %d', $rageid, $this->db->escapeString($releasename), $this->db->escapeString(substr($desc, 0, 10000)), $this->db->escapeString($genre), $this->db->escapeString($country), $imgbytes, $id));
		} else {
			$this->db->queryExec(sprintf('UPDATE tvrage SET rageid = %d, releasetitle = %s, description = %s, genre = %s, country = %s WHERE id = %d', $rageid, $this->db->escapeString($releasename), $this->db->escapeString(substr($desc, 0, 10000)), $this->db->escapeString($genre), $this->db->escapeString($country), $id));
			if ($imgbytes != '') {
				$path = nZEDb_WWW . 'covers/tvrage/' . $id . '.jpg';
				if (file_exists($path)) {
					unlink($path);
				}
				$check = file_put_contents($path, $imgbytes);
				if ($check !== false) {
					$this->db->Exec("UPDATE tvrage SET imgdata = 'x' WHERE id = " . $id);
					chmod($path, 0755);
				}
			}
		}
	}

	public function delete($id)
	{
		return $this->db->queryExec(sprintf("DELETE FROM tvrage WHERE id = %d", $id));
	}

	public function getRange($start, $num, $ragename = "")
	{
		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		$rsql = '';
		if ($ragename != "") {
			$like = 'ILIKE';
			if ($this->db->dbSystem() == 'mysql') {
				$like = 'LIKE';
			}
			$rsql .= sprintf("AND tvrage.releasetitle %s %s ", $like, $this->db->escapeString("%" . $ragename . "%"));
		}

		return $this->db->query(sprintf("SELECT id, rageid, releasetitle, description, createddate FROM tvrage WHERE 1=1 %s ORDER BY rageid ASC" . $limit, $rsql));
	}

	public function getCount($ragename = "")
	{
		$rsql = '';
		if ($ragename != "") {
			$like = 'ILIKE';
			if ($this->db->dbSystem() == 'mysql') {
				$like = 'LIKE';
			}
			$rsql .= sprintf("AND tvrage.releasetitle %s %s ", $like, $this->db->escapeString("%" . $ragename . "%"));
		}

		$res = $this->db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM tvrage WHERE 1=1 %s", $rsql));
		return $res["num"];
	}

	public function getCalendar($date = "")
	{
		if (!preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
			$date = date("Y-m-d");
		}
		$sql = sprintf("SELECT * FROM tvrageepisodes WHERE DATE(airdate) = %s ORDER BY airdate ASC", $this->db->escapeString($date));
		return $this->db->query($sql);
	}

	public function getSeriesList($uid, $letter = "", $ragename = "")
	{
		$rsql = '';
		if ($letter != "") {
			if ($letter == '0-9') {
				$letter = '[0-9]';
			}

			if ($this->db->dbSystem() == "mysql") {
				$rsql .= sprintf("AND tvrage.releasetitle REGEXP %s", $this->db->escapeString('^' . $letter));
			} else {
				$rsql .= sprintf("AND tvrage.releasetitle ~ %s", $this->db->escapeString('^' . $letter));
			}
		}
		$tsql = '';
		if ($ragename != '') {
			$tsql .= sprintf("AND tvrage.releasetitle LIKE %s", $this->db->escapeString("%" . $ragename . "%"));
		}

		if ($this->db->dbSystem() == 'mysql') {
			return $this->db->query(sprintf("SELECT tvrage.id, tvrage.rageid, tvrage.releasetitle, tvrage.genre, tvrage.country, tvrage.createddate, tvrage.prevdate, tvrage.nextdate, userseries.id as userseriesid from tvrage LEFT OUTER JOIN userseries ON userseries.userid = %d AND userseries.rageid = tvrage.rageid WHERE tvrage.rageid IN (SELECT rageid FROM releases) AND tvrage.rageid > 0 %s %s GROUP BY tvrage.rageid ORDER BY tvrage.releasetitle ASC", $uid, $rsql, $tsql));
		} else {
			return $this->db->query(sprintf("SELECT tvrage.id, tvrage.rageid, tvrage.releasetitle, tvrage.genre, tvrage.country, tvrage.createddate, tvrage.prevdate, tvrage.nextdate, userseries.id as userseriesid from tvrage LEFT OUTER JOIN userseries ON userseries.userid = %d AND userseries.rageid = tvrage.rageid WHERE tvrage.rageid IN (SELECT rageid FROM releases) AND tvrage.rageid > 0 %s %s GROUP BY tvrage.rageid, tvrage.id, userseries.id ORDER BY tvrage.releasetitle ASC", $uid, $rsql, $tsql));
		}
	}

	public function updateSchedule()
	{
		$countries = $this->db->query("SELECT DISTINCT(country) AS country FROM tvrage WHERE country != ''");
		$showsindb = $this->db->query("SELECT DISTINCT(rageid) AS rageid FROM tvrage");
		$showarray = array();
		foreach ($showsindb as $show) {
			$showarray[] = $show['rageid'];
		}
		foreach ($countries as $country) {
			if ($this->echooutput) {
				echo $this->c->headerOver('Updating schedule for: ') . $this->c->primary($country['country']);
			}

			$sched = getURL($this->xmlFullScheduleUrl . $country['country']);
			if ($sched !== false && ($xml = @simplexml_load_string($sched))) {
				$tzOffset = 60 * 60 * 6;
				$yesterday = strtotime("-1 day") - $tzOffset;
				$xmlSchedule = array();

				foreach ($xml->DAY as $sDay) {
					$currDay = strtotime($sDay['attr']);
					foreach ($sDay as $sTime) {
						$currTime = (string) $sTime['attr'];
						foreach ($sTime as $sShow) {
							$currShowName = (string) $sShow['name'];
							$currShowId = (string) $sShow->sid;
							$day_time = strtotime($sDay['attr'] . ' ' . $currTime);
							$tag = ($currDay < $yesterday) ? 'prev' : 'next';
							if ($tag == 'prev' || ($tag == 'next' && !isset($xmlSchedule[$currShowId]['next']))) {
								$xmlSchedule[$currShowId][$tag] = array('name' => $currShowName, 'day' => $currDay, 'time' => $currTime, 'day_time' => $day_time, 'day_date' => date("Y-m-d H:i:s", $day_time), 'title' => html_entity_decode((string) $sShow->title, ENT_QUOTES, 'UTF-8'), 'episode' => html_entity_decode((string) $sShow->ep, ENT_QUOTES, 'UTF-8'));
								$xmlSchedule[$currShowId]['showname'] = $currShowName;
							}

							// Only add it here, no point adding it to tvrage aswell that will automatically happen when an ep gets posted.
							if ($sShow->ep == "01x01") {
								$showarray[] = $sShow->sid;
							}

							// Only stick current shows and new shows in there.
							if (in_array($currShowId, $showarray)) {
								if ($this->db->dbSystem() == 'mysql') {
									$this->db->queryExec(sprintf("INSERT INTO tvrageepisodes (rageid, showtitle, fullep, airdate, link, eptitle) VALUES (%d, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE airdate = %s, link = %s ,eptitle = %s, showtitle = %s", $sShow->sid, $this->db->escapeString($currShowName), $this->db->escapeString($sShow->ep), $this->db->escapeString(date("Y-m-d H:i:s", $day_time)), $this->db->escapeString($sShow->link), $this->db->escapeString($sShow->title), $this->db->escapeString(date("Y-m-d H:i:s", $day_time)), $this->db->escapeString($sShow->link), $this->db->escapeString($sShow->title), $this->db->escapeString($currShowName)));
								} else if ($this->db->dbSystem() == 'pgsql') {
									$check = $this->db->queryOneRow(sprintf('SELECT id FROM tvrageepisodes WHERE rageid = %d', $sShow->sid));
									if ($check === false) {
										$this->db->queryExec(sprintf("INSERT INTO tvrageepisodes (rageid, showtitle, fullep, airdate, link, eptitle) VALUES (%d, %s, %s, %s, %s, %s)", $sShow->sid, $this->db->escapeString($currShowName), $this->db->escapeString($sShow->ep), $this->db->escapeString(date("Y-m-d H:i:s", $day_time)), $this->db->escapeString($sShow->link), $this->db->escapeString($sShow->title)));
									} else {
										$this->db->queryExec(sprintf('UPDATE tvrageepisodes SET showtitle = %s, fullep = %s, airdate = %s, link = %s, eptitle = %s WHERE id = %d', $this->db->escapeString($currShowName), $this->db->escapeString($sShow->ep), $this->db->escapeString(date("Y-m-d H:i:s", $day_time)), $this->db->escapeString($sShow->link), $this->db->escapeString($sShow->title), $check['id']));
									}
								}
							}
						}
					}
				}
				// Update series info.
				foreach ($xmlSchedule as $showId => $epInfo) {
					$res = $this->db->query(sprintf("SELECT * FROM tvrage WHERE rageid = %d", $showId));
					if (sizeof($res) > 0) {
						foreach ($res as $arr) {
							$prev_ep = $next_ep = "";
							$query = array();

							// Previous episode.
							if (isset($epInfo['prev']) && $epInfo['prev']['episode'] != '') {
								$prev_ep = $epInfo['prev']['episode'] . ', "' . $epInfo['prev']['title'] . '"';
								$query[] = sprintf("prevdate = %s, previnfo = %s", $this->db->from_unixtime($epInfo['prev']['day_time']), $this->db->escapeString($prev_ep));
							}

							// Next episode.
							if (isset($epInfo['next']) && $epInfo['next']['episode'] != '') {
								if ($prev_ep == "" && $arr['nextinfo'] != '' && $epInfo['next']['day_time'] > strtotime($arr["nextdate"]) && strtotime(date('Y-m-d', strtotime($arr["nextdate"]))) < $yesterday) {
									$this->db->queryExec(sprintf("UPDATE tvrage SET prevdate = nextdate, previnfo = nextinfo WHERE id = %d", $arr['id']));
									$prev_ep = "SWAPPED with: " . $arr['nextinfo'] . " - " . date("r", strtotime($arr["nextdate"]));
								}
								$next_ep = $epInfo['next']['episode'] . ', "' . $epInfo['next']['title'] . '"';
								$query[] = sprintf("nextdate = %s, nextinfo = %s", $this->db->from_unixtime($epInfo['next']['day_time']), $this->db->escapeString($next_ep));
							} else {
								$query[] = "nextdate = NULL, nextinfo = NULL";
							}

							// Output.
							if ($this->echooutput) {
								echo $this->c->primary($epInfo['showname'] . " (" . $showId . "):");
								if (isset($epInfo['prev']['day_time'])) {
									echo $this->c->headerOver("Prev EP: ") . $this->c->primary("{$prev_ep} - " . date("m/d/Y H:i T", $epInfo['prev']['day_time']));
								}
								if (isset($epInfo['next']['day_time'])) {
									echo $this->c->headerOver("Next EP: ") . $this->c->primary("{$next_ep} - " . date("m/d/Y H:i T", $epInfo['next']['day_time']));
								}
								echo "\n";
							}

							// Update info.
							if (count($query) > 0) {
								$sql = join(", ", $query);
								$sql = sprintf("UPDATE tvrage SET {$sql} WHERE id = %d", $arr['id']);
								$this->db->queryExec($sql);
							}
						}
					}
				}
			} else {
				// No response from tvrage.
				if ($this->echooutput) {
					echo $this->c->info("Schedule not found.");
				}
			}
		}
		if ($this->echooutput) {
			echo $this->c->primary("Updated the TVRage schedule succesfully.");
		}
	}

	public function getEpisodeInfo($rageid, $series, $episode)
	{
		$result = array('title' => '', 'airdate' => '');

		$series = str_ireplace("s", "", $series);
		$episode = str_ireplace("e", "", $episode);
		$xml = getUrl($this->xmlEpisodeInfoUrl . "&sid=" . $rageid . "&ep=" . $series . "x" . $episode);
		if ($xml !== false) {
			if (preg_match('/no show found/i', $xml)) {
				return false;
			}

			$xmlObj = @simplexml_load_string($xml);
			$arrXml = objectsIntoArray($xmlObj);
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
		$result = array('desc' => '', 'imgurl' => '');
		$page = getUrl($this->showInfoUrl . $rageid);
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

	public function getRageInfoFromService($rageid)
	{
		$result = array('genres' => '', 'country' => '', 'showid' => $rageid);
		// Full search gives us the akas.
		$xml = getUrl($this->xmlShowInfoUrl . $rageid);
		if ($xml !== false) {
			$arrXml = objectsIntoArray(simplexml_load_string($xml));
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

	// Convert 2012-24-07 to 2012-07-24, there is probably a better way
	public function checkDate($date)
	{
		if (!empty($date) && $date != NULL) {
			$chk = explode(" ", $date);
			$chkd = explode("-", $chk[0]);
			if ($chkd[1] > 12) {
				$date = date('Y-m-d H:i:s', strtotime($chkd[1] . " " . $chkd[2] . " " . $chkd[0]));
			}
			return $date;
		}
		return NULL;
	}

	public function updateEpInfo($show, $relid)
	{
		if ($this->echooutput) {
			echo $this->c->headerOver("Updating Episode: ") . $this->c->primary($show['cleanname'] . " " . $show['seriesfull'] . (($show['year'] != '') ? ' ' . $show['year'] : '') . (($show['country'] != '') ? ' [' . $show['country'] . ']' : ''));
		}

		$tvairdate = (isset($show['airdate']) && !empty($show['airdate'])) ? $this->db->escapeString($this->checkDate($show['airdate'])) : "NULL";
		$this->db->queryExec(sprintf("UPDATE releases SET seriesfull = %s, season = %s, episode = %s, tvairdate = %s WHERE id = %d", $this->db->escapeString($show['seriesfull']), $this->db->escapeString($show['season']), $this->db->escapeString($show['episode']), $tvairdate, $relid));
	}

	public function updateRageInfo($rageid, $show, $tvrShow, $relid)
	{
		// Try and get the episode specific info from tvrage.
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false) {
			$tvairdate = (!empty($epinfo['airdate'])) ? $this->db->escapeString($epinfo['airdate']) : "NULL";
			$tvtitle = (!empty($epinfo['title'])) ? $this->db->escapeString($epinfo['title']) : "NULL";

			$this->db->queryExec(sprintf("UPDATE releases set tvtitle = %s, tvairdate = %s, rageid = %d where id = %d", $this->db->escapeString(trim($tvtitle)), $tvairdate, $tvrShow['showid'], $relid));
		} else {
			$this->db->queryExec(sprintf("UPDATE releases SET rageid = %d WHERE id = %d", $tvrShow['showid'], $relid));
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
			$img = getUrl($rInfo['imgurl']);
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
			$tvairdate = (!empty($epinfo['airdate'])) ? $this->db->escapeString($epinfo['airdate']) : "NULL";
			$tvtitle = (!empty($epinfo['title'])) ? $this->db->escapeString($epinfo['title']) : "NULL";
			$this->db->queryExec(sprintf("UPDATE releases SET tvtitle = %s, tvairdate = %s, rageid = %d WHERE id = %d", $this->db->escapeString(trim($tvtitle)), $tvairdate, $traktArray['show']['tvrage_id'], $relid));
		} else {
			$this->db->queryExec(sprintf("UPDATE releases SET rageid = %d WHERE id = %d", $traktArray['show']['tvrage_id'], $relid));
		}

		$genre = '';
		if (isset($traktArray['show']['genres']) && is_array($traktArray['show']['genres']) && !empty($traktArray['show']['genres'])) {
			$genre = $traktArray['show']['genres']['0'];
		}

		$country = '';
		if (isset($traktArray['show']['country']) && !empty($traktArray['show']['country'])) {
			$country = $this->countryCode($traktArray['show']['country']);
		}

		$rInfo = $this->getRageInfoFromPage($rageid);
		$desc = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc'])) {
			$desc = $rInfo['desc'];
		}

		$imgbytes = '';
		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
			$img = getUrl($rInfo['imgurl']);
			if ($img !== false) {
				$im = @imagecreatefromstring($img);
				if ($im !== false) {
					$imgbytes = $img;
				}
			}
		}

		$this->add($rageid, $show['cleanname'], $desc, $genre, $country, $imgbytes);
	}

	public function processTvReleases($releaseToWork = '', $lookupTvRage = true, $local = false)
	{
		$ret = 0;
		$trakt = new TraktTv();

		// Get all releases without a rageid which are in a tv category.
		if ($releaseToWork == '') {
			$res = $this->db->query(sprintf("SELECT r.searchname, r.id FROM releases r INNER JOIN category c ON r.categoryid = c.id WHERE r.nzbstatus = 1 AND r.rageid = -1 AND c.parentid = %d ORDER BY postdate DESC LIMIT %d", Category::CAT_PARENT_TV, $this->rageqty));
			$tvcount = count($res);
		} else {
			$pieces = explode("           =+=            ", $releaseToWork);
			$res = array(array('searchname' => $pieces[0], 'id' => $pieces[1]));
			$tvcount = 1;
		}

		if ($this->echooutput && $tvcount > 1) {
			echo $this->c->header("Processing TV for " . $tvcount . " release(s).");
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
						echo $this->c->primaryOver("TVRage ID for ") . $this->c->headerOver($show['cleanname']) . $this->c->primary(" not found in local db, checking web.");
					}

					$tvrShow = $this->getRageMatch($show);
					if ($tvrShow !== false && is_array($tvrShow)) {
						// Get all tv info and add show.
						$this->updateRageInfo($tvrShow['showid'], $show, $tvrShow, $arr['id']);
					} else if ($tvrShow === false) {
						// If tvrage fails, try trakt.
						$traktArray = $trakt->traktTVSEsummary($show['name'], $show['season'], $show['episode']);
						if ($traktArray !== false) {
							if (isset($traktArray['show']['tvrage_id']) && $traktArray['show']['tvrage_id'] !== 0) {
								if ($this->echooutput) {
									echo $this->c->primary('Found TVRage ID on trakt:' . $traktArray['show']['tvrage_id']);
								}
								$this->updateRageInfoTrakt($traktArray['show']['tvrage_id'], $show, $traktArray, $arr['id']);
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
					} else {
						// $tvrShow probably equals -1 but we'll do this as a catchall instead of a specific else if.
						// Skip because we couldnt connect to tvrage.com.
					}
				} else if ($id > 0) {
					//if ($this->echooutput) {
					//    echo $this->c->AlternateOver("TV series: ") . $this->c->header($show['cleanname'] . " " . $show['seriesfull'] . (($show['year'] != '') ? ' ' . $show['year'] : '') . (($show['country'] != '') ? ' [' . $show['country'] . ']' : ''));
					// }
					$tvairdate = (isset($show['airdate']) && !empty($show['airdate'])) ? $this->db->escapeString($this->checkDate($show['airdate'])) : "NULL";
					$tvtitle = "NULL";

					if ($lookupTvRage) {
						$epinfo = $this->getEpisodeInfo($id, $show['season'], $show['episode']);
						if ($epinfo !== false) {
							if (isset($epinfo['airdate'])) {
								$tvairdate = $this->db->escapeString($this->checkDate($epinfo['airdate']));
							}

							if (!empty($epinfo['title'])) {
								$tvtitle = $this->db->escapeString(trim($epinfo['title']));
							}
						}
					}
					if ($tvairdate == "NULL") {
						$this->db->queryExec(sprintf('UPDATE releases SET tvtitle = %s, rageid = %d WHERE id = %d', $tvtitle, $id, $arr['id']));
					} else {
						$this->db->queryExec(sprintf('UPDATE releases SET tvtitle = %s, tvairdate = %s, rageid = %d WHERE id = %d', $tvtitle, $tvairdate, $id, $arr['id']));
					}
					// Cant find rageid, so set rageid to n/a.
				} else {
					$this->db->queryExec(sprintf('UPDATE releases SET rageid = -2 WHERE id = %d', $arr['id']));
				}
				// Not a tv episode, so set rageid to n/a.
			} else {
				$this->db->queryExec(sprintf('UPDATE releases SET rageid = -2 WHERE id = %d', $arr['id']));
			}
			$ret++;
		}
		return $ret;
	}

	public function getRageMatch($showInfo)
	{
		$title = $showInfo['cleanname'];
		// Full search gives us the akas.
		$xml = getUrl($this->xmlFullSearchUrl . urlencode(strtolower($title)));
		if ($xml !== false) {
			$arrXml = @objectsIntoArray(simplexml_load_string($xml));
			if (isset($arrXml['show']) && is_array($arrXml)) {
				// We got a valid xml response
				$titleMatches = $urlMatches = $akaMatches = array();

				if (isset($arrXml['show']['showid'])) {
					// We got exactly 1 match so lets convert it to an array so we can use it in the logic below.
					$newArr = array();
					$newArr[] = $arrXml['show'];
					unset($arrXml);
					$arrXml['show'] = $newArr;
				}

				foreach ($arrXml['show'] as $arr) {
					$titlepct = $urlpct = $akapct = 0;
					$tvrlink = '';

					// Get a match percentage based on our name and the name returned from tvr.
					$titlepct = $this->checkMatch($title, $arr['name']);
					if ($titlepct !== false) {
						$titleMatches[$titlepct][] = array('title' => $arr['name'], 'showid' => $arr['showid'], 'country' => $this->countryCode($arr['country']), 'genres' => $arr['genres'], 'tvr' => $arr);
					}

					// Get a match percentage based on our name and the url returned from tvr.
					if (isset($arr['link']) && preg_match('/tvrage\.com\/((?!shows)[^\/]*)$/i', $arr['link'], $tvrlink)) {
						$urltitle = str_replace('_', ' ', $tvrlink[1]);
						$urlpct = $this->checkMatch($title, $urltitle);
						if ($urlpct !== false) {
							$urlMatches[$urlpct][] = array('title' => $urltitle, 'showid' => $arr['showid'], 'country' => $this->countryCode($arr['country']), 'genres' => $arr['genres'], 'tvr' => $arr);
						}
					}

					// Check if there are any akas for this result and get a match percentage for them too.
					if (isset($arr['akas'])) {
						if (is_array($arr['akas']['aka'])) {
							// Multuple akas.
							foreach ($arr['akas']['aka'] as $aka) {
								$akapct = $this->checkMatch($title, $aka);
								if ($akapct !== false) {
									$akaMatches[$akapct][] = array('title' => $aka, 'showid' => $arr['showid'], 'country' => $this->countryCode($arr['country']), 'genres' => $arr['genres'], 'tvr' => $arr);
								}
							}
						} else {
							// One aka.
							$akapct = $this->checkMatch($title, $arr['akas']['aka']);
							if ($akapct !== false) {
								$akaMatches[$akapct][] = array('title' => $arr['akas']['aka'], 'showid' => $arr['showid'], 'country' => $this->countryCode($arr['country']), 'genres' => $arr['genres'], 'tvr' => $arr);
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
						echo $this->c->primary('Found 100% match: "' . $titleMatches[100][0]['title'] . '"');
					}
					return $titleMatches[100][0];
				}

				// Look for 100% url matches next.
				if (isset($urlMatches[100])) {
					if ($this->echooutput) {
						echo $this->c->primary('Found 100% url match: "' . $urlMatches[100][0]['title'] . '"');
					}
					return $urlMatches[100][0];
				}

				// Look for 100% aka matches next.
				if (isset($akaMatches[100])) {
					if ($this->echooutput) {
						echo $this->c->primary('Found 100% aka match: "' . $akaMatches[100][0]['title'] . '"');
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
						echo $this->c->primary('Found ' . $mk . '% match: "' . $titleMatches[$mk][0]['title'] . '"');
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
						echo $this->c->primary('Found ' . $ak . '% aka match: "' . $akaMatches[$ak][0]['title'] . '"');
					}
					return $akaMatches[$ak][0];
				}

				if ($this->echooutput) {
					echo $this->c->primary('No match found on TVRage trying Trakt.');
				}
				return false;
			} else {
				if ($this->echooutput) {
					echo $this->c->primary('Nothing returned from tvrage.');
				}
				return false;
			}
		} else {
			return -1;
		}

		if ($this->echooutput) {
			echo $this->c->primary('No match found online.');
		}
		return false;
	}

	public function checkMatch($ourName, $tvrName)
	{
		// Clean up name ($ourName is already clean).
		$tvrName = $this->cleanName($tvrName);
		$tvrName = preg_replace('/ of /i', '', $tvrName);
		$ourName = preg_replace('/ of /i', '', $ourName);

		// Create our arrays.
		$ourArr = explode(' ', $ourName);
		$tvrArr = explode(' ', $tvrName);

		// Set our match counts.
		$numMatches = 0;
		$totalMatches = sizeof($ourArr) + sizeof($tvrArr);

		// Loop through each array matching again the opposite value, if they match increment!
		foreach ($ourArr as $oname) {
			if (preg_match('/ ' . preg_quote($oname, '/') . ' /i', ' ' . $tvrName . ' ')) {
				$numMatches++;
			}
		}
		foreach ($tvrArr as $tname) {
			if (preg_match('/ ' . preg_quote($tname, '/') . ' /i', ' ' . $ourName . ' ')) {
				$numMatches++;
			}
		}

		// Check what we're left with.
		if ($numMatches <= 0) {
			return false;
		} else {
			$matchpct = ($numMatches / $totalMatches) * 100;
		}

		if ($matchpct >= TvRage::MATCH_PROBABILITY) {
			return $matchpct;
		} else {
			return false;
		}
	}

	public function cleanName($str)
	{
		$str = str_replace(array('.', '_'), ' ', $str);

		$str = str_replace(array('à', 'á', 'â', 'ã', 'ä', 'æ', 'À', 'Á', 'Â', 'Ã', 'Ä'), 'a', $str);
		$str = str_replace(array('ç', 'Ç'), 'c', $str);
		$str = str_replace(array('Σ', 'è', 'é', 'ê', 'ë', 'È', 'É', 'Ê', 'Ë'), 'e', $str);
		$str = str_replace(array('ì', 'í', 'î', 'ï', 'Ì', 'Í', 'Î', 'Ï'), 'i', $str);
		$str = str_replace(array('ò', 'ó', 'ô', 'õ', 'ö', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö'), 'o', $str);
		$str = str_replace(array('ù', 'ú', 'û', 'ü', 'ū', 'Ú', 'Û', 'Ü', 'Ū'), 'u', $str);
		$str = str_replace('ß', 'ss', $str);

		$str = str_replace('&', 'and', $str);
		$str = preg_replace('/^(history|discovery) channel/i', '', $str);
		$str = str_replace(array('\'', ':', '!', '"', '#', '*', '’', ',', '(', ')', '?'), '', $str);
		$str = str_replace('$', 's', $str);
		$str = preg_replace('/\s{2,}/', ' ', $str);

		$str = trim($str, '\"');
		return trim($str);
	}

	public function parseNameEpSeason($relname)
	{
		$relname = trim(preg_replace('/ US | UK |EnJoY!|GOU[\.\-_ ](Der)?|SecretUsenet\scom|TcP[\.\-_ ]|usenet4ever\sinfo(\sund)?/i', '', $relname));
		$showInfo = array('name' => '', 'season' => '', 'episode' => '', 'seriesfull' => '', 'airdate' => '', 'country' => '', 'year' => '', 'cleanname' => '');
		$matches = '';

		// S01E01-E02 and S01E01-02
		if (preg_match('/^(.*?)[\. ]s(\d{1,2})\.?e(\d{1,3})(?:\-e?|\-?e)(\d{1,3})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = array(intval($matches[3]), intval($matches[4]));
		}
		//S01E0102 - lame no delimit numbering, regex would collide if there was ever 1000 ep season.
		else if (preg_match('/^(.*?)[\. ]s(\d{2})\.?e(\d{2})(\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = array(intval($matches[3]), intval($matches[4]));
		}
		// S01E01 and S01.E01
		else if (preg_match('/^(.*?)[\. ]s(\d{1,2})\.?e(\d{1,3})\.?/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = intval($matches[3]);
		}
		// S01
		else if (preg_match('/^(.*?)[\. ]s(\d{1,2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}
		// S01D1 and S1D1
		else if (preg_match('/^(.*?)[\. ]s(\d{1,2})d\d{1}\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}
		// 1x01
		else if (preg_match('/^(.*?)[\. ](\d{1,2})x(\d{1,3})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = intval($matches[3]);
		}
		// 2009.01.01 and 2009-01-01
		else if (preg_match('/^(.*?)[\. ](19|20)(\d{2})[\.\-](\d{2})[\.\-](\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = $matches[2] . $matches[3];
			$showInfo['episode'] = $matches[4] . '/' . $matches[5];
			$showInfo['airdate'] = $matches[2] . $matches[3] . '-' . $matches[4] . '-' . $matches[5]; //yy-m-d
		}
		// 01.01.2009
		else if (preg_match('/^(.*?)[\. ](\d{2}).(\d{2})\.(19|20)(\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = $matches[4] . $matches[5];
			$showInfo['episode'] = $matches[2] . '/' . $matches[3];
			$showInfo['airdate'] = $matches[4] . $matches[5] . '-' . $matches[2] . '-' . $matches[3]; //yy-m-d
		}
		// 01.01.09
		else if (preg_match('/^(.*?)[\. ](\d{2}).(\d{2})\.(\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = ($matches[4] <= 99 && $matches[4] > 15) ? '19' . $matches[4] : '20' . $matches[4];
			$showInfo['episode'] = $matches[2] . '/' . $matches[3];
			$showInfo['airdate'] = $showInfo['season'] . '-' . $matches[2] . '-' . $matches[3]; //yy-m-d
		}
		// 2009.E01
		else if (preg_match('/^(.*?)[\. ]20(\d{2})\.e(\d{1,3})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = '20' . $matches[2];
			$showInfo['episode'] = intval($matches[3]);
		}
		// 2009.Part1
		else if (preg_match('/^(.*?)[\. ]20(\d{2})\.Part(\d{1,2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = '20' . $matches[2];
			$showInfo['episode'] = intval($matches[3]);
		}
		// Part1/Pt1
		else if (preg_match('/^(.*?)[\. ](?:Part|Pt)\.?(\d{1,2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$showInfo['episode'] = intval($matches[2]);
		}
		//The.Pacific.Pt.VI.HDTV.XviD-XII / Part.IV
		else if (preg_match('/^(.*?)[\. ](?:Part|Pt)\.?([ivx]+)/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$epLow = strtolower($matches[2]);
			switch ($epLow) {
				case 'i': $e = 1;
					break;
				case 'ii': $e = 2;
					break;
				case 'iii': $e = 3;
					break;
				case 'iv': $e = 4;
					break;
				case 'v': $e = 5;
					break;
				case 'vi': $e = 6;
					break;
				case 'vii': $e = 7;
					break;
				case 'viii': $e = 8;
					break;
				case 'ix': $e = 9;
					break;
				case 'x': $e = 10;
					break;
				case 'xi': $e = 11;
					break;
				case 'xii': $e = 12;
					break;
				case 'xiii': $e = 13;
					break;
				case 'xiv': $e = 14;
					break;
				case 'xv': $e = 15;
					break;
				case 'xvi': $e = 16;
					break;
				case 'xvii': $e = 17;
					break;
				case 'xviii': $e = 18;
					break;
				case 'xix': $e = 19;
					break;
				case 'xx': $e = 20;
					break;
			}
			$showInfo['episode'] = $e;
		}
		// Band.Of.Brothers.EP06.Bastogne.DVDRiP.XviD-DEiTY
		else if (preg_match('/^(.*?)[\. ]EP?\.?(\d{1,3})/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$showInfo['episode'] = intval($matches[2]);
		}
		// Season.1
		else if (preg_match('/^(.*?)[\. ]Seasons?\.?(\d{1,2})/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}

		if (!empty($showInfo['name'])) {
			$countryMatch = $yearMatch = '';
			// Country or origin matching.
			if (preg_match('/[\._ ](US|UK|AU|NZ|CA|NL|Canada|Australia|America|United States|United Kingdom)/', $showInfo['name'], $countryMatch)) {
				if (strtolower($countryMatch[1]) == 'canada') {
					$showInfo['country'] = 'CA';
				} else if (strtolower($countryMatch[1]) == 'australia') {
					$showInfo['country'] = 'AU';
				} else if (strtolower($countryMatch[1]) == 'america' || strtolower($countryMatch[1]) == 'united states') {
					$showInfo['country'] = 'US';
				} else if (strtolower($countryMatch[1]) == 'united kingdom') {
					$showInfo['country'] = 'UK';
				} else {
					$showInfo['country'] = strtoupper($countryMatch[1]);
				}
			}

			// Clean show name.
			$showInfo['cleanname'] = $this->cleanName($showInfo['name']);

			// Check for dates instead of seasons.
			if (strlen($showInfo['season']) == 4) {
				$showInfo['seriesfull'] = $showInfo['season'] . "/" . $showInfo['episode'];
			} else {
				// Get year if present (not for releases with dates as seasons).
				if (preg_match('/[\._ ](19|20)(\d{2})/i', $relname, $yearMatch)) {
					$showInfo['year'] = $yearMatch[1] . $yearMatch[2];
				}

				$showInfo['season'] = sprintf('S%02d', $showInfo['season']);
				// Check for multi episode release.
				if (is_array($showInfo['episode'])) {
					$tmpArr = array();
					foreach ($showInfo['episode'] as $ep) {
						$tmpArr[] = sprintf('E%02d', $ep);
					}
					$showInfo['episode'] = implode('', $tmpArr);
				} else {
					$showInfo['episode'] = sprintf('E%02d', $showInfo['episode']);
				}

				$showInfo['seriesfull'] = $showInfo['season'] . $showInfo['episode'];
			}
			$showInfo['airdate'] = (!empty($showInfo['airdate'])) ? $showInfo['airdate'] . ' 00:00:00' : '';
			return $showInfo;
		}
		return false;
	}

	public function getGenres()
	{
		return array('Action', 'Adult/Porn', 'Adventure', 'Anthology', 'Arts & Crafts', 'Automobiles', 'Buy, Sell & Trade', 'Celebrities', 'Children', 'Cinema/Theatre', 'Comedy', 'Cooking/Food', 'Crime', 'Current Events',
			'Dance', 'Debate', 'Design/Decorating', 'Discovery/Science', 'Drama', 'Educational', 'Family', 'Fantasy', 'Fashion/Make-up', 'Financial/Business', 'Fitness', 'Garden/Landscape', 'History',
			'Horror/Supernatural', 'Housing/Building', 'How To/Do It Yourself', 'Interview', 'Lifestyle', 'Literature', 'Medical', 'Military/War', 'Music', 'Mystery', 'Pets/Animals', 'Politics', 'Puppets',
			'Religion', 'Romance/Dating', 'Sci-Fi', 'Sketch/Improv', 'Soaps', 'Sports', 'Super Heroes', 'Talent', 'Tech/Gaming', 'Teens', 'Thriller', 'Travel', 'Western', 'Wildlife');
	}

}

?>
