<?php
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/trakttv.php");
require_once(WWW_DIR."/lib/site.php");

class TvRage
{
	const APIKEY = '7FwjZ8loweFcOhHfnU3E';
	const MATCH_PROBABILITY = 75;

	function TvRage($echooutput=false)
	{
		$s = new Sites();
		$site = $s->get();
		$this-> rageqty = (!empty($site->maxrageprocessed)) ? $site->maxrageprocessed : 75;
		$this->echooutput = $echooutput;

		$this->xmlFullSearchUrl = "http://services.tvrage.com/feeds/full_search.php?show=";
		$this->xmlFullShowInfoUrl = "http://services.tvrage.com/feeds/full_show_info.php?sid=";
		$this->xmlEpisodeInfoUrl = "http://services.tvrage.com/myfeeds/episodeinfo.php?key=".TvRage::APIKEY;
		$this->xmlFullScheduleUrl = "http://services.tvrage.com/feeds/fullschedule.php?country=";

		$this->showInfoUrl = "http://www.tvrage.com/shows/id-";
	}

	public function getByID($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM tvrage WHERE id = %d", $id));
	}

	public function getByRageID($id)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM tvrage WHERE rageid = %d", $id));
	}

	public function getByTitle($title)
	{
		// Check if we already have an entry for this show.
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE LOWER(releasetitle) = LOWER(%s)", $db->escapeString($title)));
		if ($res)
			return $res["rageid"];

		$title2 = str_replace(' and ', ' & ', $title);
		$res = $db->queryOneRow(sprintf("SELECT rageid FROM tvrage WHERE LOWER(releasetitle) = LOWER(%s)", $db->escapeString($title2)));
		if ($res)
			return $res["rageid"];

		return false;
	}

	public function add($rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{
		$releasename = str_replace(array('.','_'), array(' ',' '), $releasename);
		$db = new DB();
		if (strlen($country) > 2)
		{
			$code = $db->queryOneRow('SELECT code FROM country WHERE LOWER(name) = LOWER('.$db->escapeString($country).')');
			if (isset($code['code']))
				$country = $code['code'];
		}
		$ckmsg = $db->queryOneRow('SELECT id FROM tvrage WHERE rageid = '.$rageid);
		if ($db->dbSystem() == 'mysql')
		{
			if ($ckmsg === false)
				$db->queryExec(sprintf('INSERT INTO tvrage (rageid, releasetitle, description, genre, country, createddate, imgdata) VALUES (%s, %s, %s, %s, %s, NOW(), %s)', $rageid, $db->escapeString($releasename), $db->escapeString(substr($desc, 0, 10000)), $db->escapeString(substr($genre, 0, 64)), $db->escapeString($country), $db->escapeString($imgbytes)));
			else
				$db->queryExec(sprintf('UPDATE tvrage SET releasetitle = %s, description = %s, genre = %s, country = %s, createddate = NOW(), imgdata = %s WHERE id = %d', $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString(substr($genre, 0, 64)), $db->escapeString($country), $db->escapeString($imgbytes), $ckmsg['id']));
		}
		else
		{
			if ($ckmsg === false)
				$id = $db->queryInsert(sprintf('INSERT INTO tvrage (rageid, releasetitle, description, genre, country, createddate) VALUES (%d, %s, %s, %s, %s, NOW())', $rageid, $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString(substr($genre, 0, 64)), $db->escapeString($country)));
			else
			{
				$id = $ckmsg['id'];
				$db->queryExec(sprintf('UPDATE tvrage SET releasetitle = %s, description = %s, genre = %s, country = %s, createddate = NOW() WHERE id = %d', $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString(substr($genre, 0, 64)), $db->escapeString($country), $ckmsg['id']));
			}
			if ($imgbytes != '')
			{
				$path = WWW_DIR.'covers/tvrage/'.$id.'.jpg';
				if (file_exists($path))
					unlink($path);
				$check = file_put_contents($path, $imgbytes);
				if ($check !== false)
				{
					$db->Exec("UPDATE tvrage SET imgdata = 'x' WHERE id = ".$id);
					chmod($path, 0755);
				}
			}
		}
	}

	public function update($id, $rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{
		$db = new DB();
		if ($db->dbSystem() == 'mysql')
		{
			if ($imgbytes != '')
				$imgbytes = ', imgdata = '.$db->escapeString($imgbytes);

			$db->queryExec(sprintf('UPDATE tvrage SET rageid = %d, releasetitle = %s, description = %s, genre = %s, country = %s %s WHERE id = %d', $rageid, $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString($genre), $db->escapeString($country), $imgbytes, $id ));
		}
		else
		{
			$db->queryExec(sprintf('UPDATE tvrage SET rageid = %d, releasetitle = %s, description = %s, genre = %s, country = %s WHERE id = %d', $rageid, $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString($genre), $db->escapeString($country), $id ));
			if ($imgbytes != '')
			{
				$path = WWW_DIR.'covers/tvrage/'.$id.'.jpg';
				if (file_exists($path))
					unlink($path);
				$check = file_put_contents($path, $imgbytes);
				if ($check !== false)
				{
					$db->Exec("UPDATE tvrage SET imgdata = 'x' WHERE id = ".$id);
					chmod($path, 0755);
				}
			}
		}
	}

	public function delete($id)
	{
		$db = new DB();
		return $db->queryExec(sprintf("DELETE FROM tvrage WHERE id = %d", $id));
	}

	public function getRange($start, $num, $ragename="")
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		$rsql = '';
		if ($ragename != "")
		{
			$like = 'ILIKE';
			if ($db->dbSystem() == 'mysql')
				$like = 'LIKE';
			$rsql .= sprintf("AND tvrage.releasetitle %s %s ", $like, $db->escapeString("%".$ragename."%"));
		}

		return $db->query(sprintf("SELECT id, rageid, releasetitle, description, createddate FROM tvrage WHERE 1=1 %s ORDER BY rageid ASC".$limit, $rsql));
	}

	public function getCount($ragename="")
	{
		$db = new DB();

		$rsql = '';
		if ($ragename != "")
		{
			$like = 'ILIKE';
			if ($db->dbSystem() == 'mysql')
				$like = 'LIKE';
			$rsql .= sprintf("AND tvrage.releasetitle %s %s ", $like, $db->escapeString("%".$ragename."%"));
		}

		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM tvrage WHERE 1=1 %s", $rsql));
		return $res["num"];
	}

	public function getCalendar($date = "")
	{
		$db = new DB();
		if(!preg_match('/\d{4}-\d{2}-\d{2}/',$date))
			$date = date("Y-m-d");
		$sql = sprintf("SELECT * FROM tvrageepisodes WHERE DATE(airdate) = %s ORDER BY airdate ASC", $db->escapeString($date));
		return $db->query($sql);
	}

	public function getSeriesList($uid, $letter="", $ragename="")
	{
		$db = new DB();

		$rsql = '';
		if ($letter != "")
		{
			if ($letter == '0-9')
				$letter = '[0-9]';

			if ($db->dbSystem() == "mysql")
				$rsql .= sprintf("AND tvrage.releasetitle REGEXP %s", $db->escapeString('^'.$letter));
			else
				$rsql .= sprintf("AND tvrage.releasetitle ~ %s", $db->escapeString('^'.$letter));
		}
		$tsql = '';
		if ($ragename != '')
			$tsql .= sprintf("AND tvrage.releasetitle LIKE %s", $db->escapeString("%".$ragename."%"));

		if ($db->dbSystem() == 'mysql')
			return $db->query(sprintf("SELECT tvrage.id, tvrage.rageid, tvrage.releasetitle, tvrage.genre, tvrage.country, tvrage.createddate, tvrage.prevdate, tvrage.nextdate, userseries.id as userseriesid from tvrage LEFT OUTER JOIN userseries ON userseries.userid = %d AND userseries.rageid = tvrage.rageid WHERE tvrage.rageid IN (SELECT rageid FROM releases) AND tvrage.rageid > 0 %s %s GROUP BY tvrage.rageid ORDER BY tvrage.releasetitle ASC", $uid, $rsql, $tsql));
		else
			return $db->query(sprintf("SELECT tvrage.id, tvrage.rageid, tvrage.releasetitle, tvrage.genre, tvrage.country, tvrage.createddate, tvrage.prevdate, tvrage.nextdate, userseries.id as userseriesid from tvrage LEFT OUTER JOIN userseries ON userseries.userid = %d AND userseries.rageid = tvrage.rageid WHERE tvrage.rageid IN (SELECT rageid FROM releases) AND tvrage.rageid > 0 %s %s GROUP BY tvrage.rageid, tvrage.id, userseries.id ORDER BY tvrage.releasetitle ASC", $uid, $rsql, $tsql));
	}

	public function updateSchedule()
	{
		$db = new DB();

		$countries = $db->query("SELECT DISTINCT(country) AS country FROM tvrage WHERE country != ''");
		$showsindb = $db->query("SELECT DISTINCT(rageid) AS rageid FROM tvrage");
		$showarray = array();
		foreach($showsindb as $show)
		{
			$showarray[] = $show['rageid'];
		}
		foreach($countries as $country)
		{
			if ($this->echooutput)
				echo 'Updating schedule for '.$country['country'].".\n";

			$sched = getURL($this->xmlFullScheduleUrl.$country['country']);
			if ($sched !== false && ($xml = @simplexml_load_string($sched)))
			{
				$tzOffset = 60*60*6;
				$yesterday = strtotime("-1 day") - $tzOffset;
				$xmlSchedule = array();

				foreach ($xml->DAY as $sDay)
				{
					$currDay = strtotime($sDay['attr']);
					foreach ($sDay as $sTime)
					{
						$currTime = (string)$sTime['attr'];
						foreach ($sTime as $sShow)
						{
							$currShowName = (string) $sShow['name'];
							$currShowId = (string) $sShow->sid;
							$day_time= strtotime($sDay['attr'].' '.$currTime);
							$tag = ($currDay < $yesterday) ? 'prev' : 'next';
							if ($tag == 'prev' || ($tag == 'next' && !isset($xmlSchedule[$currShowId]['next'])))
							{
								$xmlSchedule[$currShowId][$tag] = array('name'=> $currShowName, 'day' => $currDay, 'time' => $currTime, 'day_time' => $day_time, 'day_date' => date("Y-m-d H:i:s", $day_time), 'title' => html_entity_decode((string)$sShow->title, ENT_QUOTES, 'UTF-8'), 'episode' =>  html_entity_decode((string)$sShow->ep, ENT_QUOTES, 'UTF-8'));
								$xmlSchedule[$currShowId]['showname'] = $currShowName;
							}

							// Only add it here, no point adding it to tvrage aswell that will automatically happen when an ep gets posted.
							if($sShow->ep == "01x01")
								$showarray[] = $sShow->sid;

							// Only stick current shows and new shows in there.
							if(in_array($currShowId,$showarray))
							{
								if ($db->dbSystem() == 'mysql')
									$db->queryExec(sprintf("INSERT INTO tvrageepisodes (rageid, showtitle, fullep, airdate, link, eptitle) VALUES (%d, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE airdate = %s, link = %s ,eptitle = %s, showtitle = %s", $sShow->sid, $db->escapeString($currShowName), $db->escapeString($sShow->ep), $db->escapeString(date("Y-m-d H:i:s", $day_time)), $db->escapeString($sShow->link), $db->escapeString($sShow->title), $db->escapeString(date("Y-m-d H:i:s", $day_time)), $db->escapeString($sShow->link), $db->escapeString($sShow->title), $db->escapeString($currShowName)));
								else if ($db->dbSystem() == 'pgsql')
								{
									$check = $db->queryOneRow(sprintf('SELECT id FROM tvrageepisodes WHERE rageid = %d', $sShow->sid));
									if ($check === false)
										$db->queryExec(sprintf("INSERT INTO tvrageepisodes (rageid, showtitle, fullep, airdate, link, eptitle) VALUES (%d, %s, %s, %s, %s, %s)", $sShow->sid, $db->escapeString($currShowName), $db->escapeString($sShow->ep), $db->escapeString(date("Y-m-d H:i:s", $day_time)), $db->escapeString($sShow->link), $db->escapeString($sShow->title)));
									else
										$db->queryExec(sprintf('UPDATE tvrageepisodes SET showtitle = %s, fullep = %s, airdate = %s, link = %s, eptitle = %s WHERE id = %d', $db->escapeString($currShowName), $db->escapeString($sShow->ep), $db->escapeString(date("Y-m-d H:i:s", $day_time)), $db->escapeString($sShow->link), $db->escapeString($sShow->title), $check['id']));
								}
							}
						}
					}
				}
				// Update series info.
				foreach ($xmlSchedule as $showId=>$epInfo)
				{
					$res = $db->query(sprintf("SELECT * FROM tvrage WHERE rageid = %d", $showId));
					if (sizeof($res) > 0)
					{
						foreach ($res as $arr)
						{
							$prev_ep = $next_ep = "";
							$query = array();

							// Previous episode.
							if (isset($epInfo['prev']) && $epInfo['prev']['episode'] != '')
							{
								$prev_ep = $epInfo['prev']['episode'].', "'.$epInfo['prev']['title'].'"';
								$query[] = sprintf("prevdate = %s, previnfo = %s", $db->from_unixtime($epInfo['prev']['day_time']), $db->escapeString($prev_ep));
							}

							// Next episode.
							if (isset($epInfo['next']) && $epInfo['next']['episode'] != '')
							{
								if ($prev_ep == "" && $arr['nextinfo'] != '' && $epInfo['next']['day_time'] > strtotime($arr["nextdate"]) && strtotime(date('Y-m-d', strtotime($arr["nextdate"]))) < $yesterday)
								{
									$db->queryExec(sprintf("UPDATE tvrage SET prevdate = nextdate, previnfo = nextinfo WHERE id = %d", $arr['id']));
									$prev_ep = "SWAPPED with: ".$arr['nextinfo']." - ".date("r", strtotime($arr["nextdate"]));
								}
								$next_ep = $epInfo['next']['episode'].', "'.$epInfo['next']['title'].'"';
								$query[] = sprintf("nextdate = %s, nextinfo = %s", $db->from_unixtime($epInfo['next']['day_time']), $db->escapeString($next_ep));
							}
							else
								$query[] = "nextdate = NULL, nextinfo = NULL";

							// Output.
							if ($this->echooutput)
							{
								echo $epInfo['showname']." (".$showId."):\n";
								if (isset($epInfo['prev']['day_time']))
									echo "Prev EP: {$prev_ep} - ".date("m/d/Y H:i T", $epInfo['prev']['day_time']).".\n";
								if (isset($epInfo['next']['day_time']))
									echo "Next EP: {$next_ep} - ".date("m/d/Y H:i T", $epInfo['next']['day_time']).".\n";
								echo "\n";
							}

							// Update info.
							if (count($query) > 0)
							{
								$sql = join(", ", $query);
								$sql = sprintf("UPDATE tvrage SET {$sql} WHERE id = %d", $arr['id']);
								$db->queryExec($sql);
							}
						}
					}
				}
			}
			else
			{
				// No response from tvrage.
				if ($this->echooutput)
					echo "Schedule not found.\n";
			}
		}
		if ($this->echooutput)
			echo "Updated the TVRage schedule succesfully.\n";
	}

	public function getEpisodeInfo($rageid, $series, $episode)
	{
		$result = array('title'=>'', 'airdate'=>'');

		$series = str_ireplace("s", "", $series);
		$episode = str_ireplace("e", "", $episode);
		$xml = getUrl($this->xmlEpisodeInfoUrl."&sid=".$rageid."&ep=".$series."x".$episode);
		if ($xml !== false)
		{
			if (preg_match('/no show found/i', $xml))
				return false;

			$xmlObj = @simplexml_load_string($xml);
			$arrXml = objectsIntoArray($xmlObj);
			if (is_array($arrXml))
			{
				if (isset($arrXml['episode']['airdate']) && $arrXml['episode']['airdate'] != '0000-00-00')
					$result['airdate'] = $arrXml['episode']['airdate'];
				if (isset($arrXml['episode']['title']))
					$result['title'] = $arrXml['episode']['title'];

				return $result;
			}
			return false;
		}
		return false;
	}

	public function getRageInfoFromPage($rageid)
	{
		$result = array('desc'=>'', 'imgurl'=>'');
		$page = getUrl($this->showInfoUrl.$rageid);
		if ($page !== false)
		{
			// Description.
			preg_match('@<div class="show_synopsis">(.*?)</div>@is', $page, $matches);
			if (isset($matches[1]))
			{
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
			if (isset($matches[1]))
				$result['imgurl'] = $matches[1];
		}
		return $result;
	}

	public function getRageInfoFromService($rageid)
	{
		$result = array('genres'=>'', 'country'=>'', 'showid'=>$rageid);
		// Full search gives us the akas.
		$xml = getUrl($this->xmlFullShowInfoUrl.$rageid);
		if ($xml !== false)
		{
			$arrXml = objectsIntoArray(simplexml_load_string($xml));
			if (is_array($arrXml))
			{
				$result['genres'] = (isset($arrXml['genres'])) ? $arrXml['genres'] : '';
				$result['country'] = (isset($arrXml['origin_country'])) ? $arrXml['origin_country'] : '';
				return $result;
			}
			return false;
		}
		return false;
	}

	//Convert 2012-24-07 to 2012-07-24, there is probably a better way
	public function checkDate($date)
	{
		if (!empty($date))
		{
			$chk = explode(" ", $date);
			$chkd = explode("-", $chk[0]);
			if ($chkd[1] > 12)
				$date = date('Y-m-d', strtotime($chkd[1]." ".$chkd[2]." ".$chkd[0]))." ".$chk[1];
		}
		return $date;
	}

	public function updateEpInfo($show, $relid)
	{
		$db = new DB();
		if ($this->echooutput)
			echo "TV series: ".$show['name']." ".$show['seriesfull'].(($show['year']!='')?' '.$show['year']:'').(($show['country']!='')?' ['.$show['country'].']':'')."\n";

		$tvairdate = (isset($show['airdate']) && !empty($show['airdate'])) ? $db->escapeString($this->checkDate($show['airdate'])) : "NULL";
		$db->queryExec(sprintf("UPDATE releases SET seriesfull = %s, season = %s, episode = %s, tvairdate = %s WHERE id = %d", $db->escapeString($show['seriesfull']), $db->escapeString($show['season']), $db->escapeString($show['episode']), $tvairdate, $relid));
	}

	public function updateRageInfo($rageid, $show, $tvrShow, $relid)
	{
		$db = new DB();

		// Try and get the episode specific info from tvrage.
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false)
		{
			$tvairdate = (!empty($epinfo['airdate'])) ? $db->escapeString($epinfo['airdate']) : "null";
			$tvtitle = (!empty($epinfo['title'])) ? $db->escapeString($epinfo['title']) : "null";

			$db->queryExec(sprintf("UPDATE releases set tvtitle = %s, tvairdate = %s, rageid = %d where id = %d", $db->escapeString(trim($tvtitle)), $tvairdate, $tvrShow['showid'], $relid));
		}
		else
			$db->queryExec(sprintf("UPDATE releases SET rageid = %d WHERE id = %d", $tvrShow['showid'], $relid));

		$genre = '';
		if (isset($tvrShow['genres']) && is_array($tvrShow['genres']) && !empty($tvrShow['genres']))
		{
			if (is_array($tvrShow['genres']['genre']))
				$genre = @implode('|', $tvrShow['genres']['genre']);
			else
				$genre = $tvrShow['genres']['genre'];
		}

		$country = '';
		if (isset($tvrShow['country']) && !empty($tvrShow['country']))
			$country = $tvrShow['country'];

		$rInfo = $this->getRageInfoFromPage($rageid);
		$desc = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc']))
			$desc = $rInfo['desc'];

		$imgbytes = '';
		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl']))
		{
			$img = getUrl($rInfo['imgurl']);
			if ($img !== false)
			{
				$im = @imagecreatefromstring($img);
				if($im !== false)
					$imgbytes = $img;
			}
		}
		$this->add($rageid, $show['cleanname'], $desc, $genre, $country, $imgbytes);
	}

	public function updateRageInfoTrakt($rageid, $show, $traktArray, $relid)
	{
		$db = new DB();

		// Try and get the episode specific info from tvrage.
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false)
		{
			$tvairdate = (!empty($epinfo['airdate'])) ? $db->escapeString($epinfo['airdate']) : "null";
			$tvtitle = (!empty($epinfo['title'])) ? $db->escapeString($epinfo['title']) : "null";
			$db->queryExec(sprintf("UPDATE releases SET tvtitle = %s, tvairdate = %s, rageid = %d WHERE id = %d", $db->escapeString(trim($tvtitle)), $tvairdate, $traktArray['show']['tvrage_id'], $relid));
		}
		else
			$db->queryExec(sprintf("UPDATE releases SET rageid = %d WHERE id = %d", $traktArray['show']['tvrage_id'], $relid));

		$genre = '';
		if (isset($traktArray['show']['genres']) && is_array($traktArray['show']['genres']) && !empty($traktArray['show']['genres']))
			$genre = $traktArray['show']['genres']['0'];

		$country = '';
		if (isset($traktArray['show']['country']) && !empty($traktArray['show']['country']))
			$country = $traktArray['show']['country'];

		$rInfo = $this->getRageInfoFromPage($rageid);
		$desc = '';
		if (isset($rInfo['desc']) && !empty($rInfo['desc']))
			$desc = $rInfo['desc'];

		$imgbytes = '';
		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl']))
		{
			$img = getUrl($rInfo['imgurl']);
			if ($img !== false)
			{
				$im = @imagecreatefromstring($img);
				if($im !== false)
					$imgbytes = $img;
			}
		}

		$this->add($rageid, $show['cleanname'], $desc, $genre, $country, $imgbytes);
	}

	public function processTvReleases($releaseToWork = '', $lookupTvRage=true)
	{
		$ret = 0;
		$db = new DB();
		$trakt = new Trakttv();
		$site = new Sites();

		// Get all releases without a rageid which are in a tv category.
		if ($releaseToWork == '')
		{
			$res = $db->query(sprintf("SELECT searchname, id FROM releases WHERE rageid = -1 AND nzbstatus = 1 AND categoryid IN (SELECT id FROM category WHERE parentid = %d) AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) LIMIT %d", Category::CAT_PARENT_TV, $this->rageqty));
			$tvcount = count($res);
		}
		else
		{
			$pieces = explode("           =+=            ", $releaseToWork);
			$res = array(array('searchname' => $pieces[0], 'id' => $pieces[1]));
			$tvcount = 1;
		}

		if ($this->echooutput && $tvcount > 1)
			echo "Processing TV for ".$tvcount." release(s).\n";

		foreach ($res as $arr)
		{
			$show = $this->parseNameEpSeason($arr['searchname']);
			if (is_array($show) && $show['name'] != '')
			{
				// Update release with season, ep, and airdate info (if available) from releasetitle.
				$this->updateEpInfo($show, $arr['id']);

				// Find the rageID.
				$id = $this->getByTitle($show['cleanname']);

				if ($id === false && $lookupTvRage)
				{
					// If it doesnt exist locally and lookups are allowed lets try to get it.
					if ($this->echooutput)
						echo "TVRage ID for '".$show['cleanname']."' not found in local db, checking web.\n";

					$tvrShow = $this->getRageMatch($show);
					if ($tvrShow !== false && is_array($tvrShow))
					{
						// Get all tv info and add show.
						$this->updateRageInfo($tvrShow['showid'], $show, $tvrShow, $arr['id']);
					}
					elseif ($tvrShow === false)
					{
						// If tvrage fails, try trakt.
						$traktArray = $trakt->traktTVSEsummary($show['name'], $show['season'], $show['episode']);
						if ($traktArray !== false)
						{
							if(isset($traktArray['show']['tvrage_id']) && $traktArray['show']['tvrage_id'] !== 0)
							{
								if ($this->echooutput)
									echo 'Found TVRage ID on trakt :'.$traktArray['show']['tvrage_id']."\n";
								$this->updateRageInfoTrakt($traktArray['show']['tvrage_id'], $show, $traktArray, $arr['id']);
							}
							// No match, add to tvrage with rageID = -2 and $show['cleanname'] title only.
							else
								$this->add(-2, $show['cleanname'], '', '', '', '');
						}
						// No match, add to tvrage with rageID = -2 and $show['cleanname'] title only.
						else
							$this->add(-2, $show['cleanname'], '', '', '', '');
					}
					else
					{
						// $tvrShow probably equals -1 but we'll do this as a catchall instead of a specific elseif.
						// Skip because we couldnt connect to tvrage.com.
					}

				}
				elseif ($id > 0)
				{
					$tvairdate = (isset($show['airdate']) && !empty($show['airdate'])) ? $db->escapeString($this->checkDate($show['airdate'])) : "NULL";
					$tvtitle = "NULL";

					if ($lookupTvRage)
					{
						$epinfo = $this->getEpisodeInfo($id, $show['season'], $show['episode']);
						if ($epinfo !== false)
						{
							if (isset($epinfo['airdate']) && !empty($epinfo['airdate']))
								$tvairdate = $db->escapeString($this->checkDate($epinfo['airdate']));

							if (!empty($epinfo['title']))
								$tvtitle = $db->escapeString($epinfo['title']);
						}
					}
					$db->queryExec(sprintf("UPDATE releases SET tvtitle = %s, tvairdate = %s, rageid = %d WHERE id = %d", $db->escapeString(trim($tvtitle)), $tvairdate, $id, $arr["id"]));
				}
				// Cant find rageid, so set rageid to n/a.
				else
					$db->queryExec(sprintf("UPDATE releases SET rageid = -2 WHERE id = %d", $arr["id"]));
			}
			// Not a tv episode, so set rageid to n/a.
			else
				$db->queryExec(sprintf("UPDATE releases SET rageid = -2 WHERE id = %d", $arr["id"]));
			$ret++;
		}
		return $ret;
	}

	public function getRageMatch($showInfo)
	{
		$title = $showInfo['cleanname'];
		// Full search gives us the akas.
		$xml = getUrl($this->xmlFullSearchUrl.urlencode(strtolower($title)));
		if ($xml !== false)
		{
			$arrXml = objectsIntoArray(simplexml_load_string($xml));
			if (isset($arrXml['show']) && is_array($arrXml['show']))
			{
				// We got a valid xml response
				$titleMatches = $urlMatches = $akaMatches = array();

				if (isset($arrXml['show']['showid']))
				{
					// We got exactly 1 match so lets convert it to an array so we can use it in the logic below.
					$newArr = array();
					$newArr[] = $arrXml['show'];
					unset($arrXml);
					$arrXml['show'] = $newArr;
				}

				foreach ($arrXml['show'] as $arr)
				{
					$titlepct = $urlpct = $akapct = 0;

					// Get a match percentage based on our name and the name returned from tvr.
					$titlepct = $this->checkMatch($title, $arr['name']);
					if ($titlepct !== false)
						$titleMatches[$titlepct][] = array('title'=>$arr['name'], 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);

					// Get a match percentage based on our name and the url returned from tvr.
					if (isset($arr['link']) && preg_match('/tvrage\.com\/((?!shows)[^\/]*)$/i', $arr['link'], $tvrlink))
					{
						$urltitle = str_replace('_', ' ', $tvrlink[1]);
						$urlpct = $this->checkMatch($title, $urltitle);
						if ($urlpct !== false)
							$urlMatches[$urlpct][] = array('title'=>$urltitle, 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
					}

					// Check if there are any akas for this result and get a match percentage for them too.
					if (isset($arr['akas']))
					{
						if (is_array($arr['akas']['aka']))
						{
							// Multuple akas.
							foreach($arr['akas']['aka'] as $aka)
							{
								$akapct = $this->checkMatch($title, $aka);
								if ($akapct !== false)
									$akaMatches[$akapct][] = array('title'=>$aka, 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
							}
						}
						else
						{
							// One aka.
							$akapct = $this->checkMatch($title, $arr['akas']['aka']);
							if ($akapct !== false)
								$akaMatches[$akapct][] = array('title'=>$arr['akas']['aka'], 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
						}
					}
				}

				// Reverse sort our matches so highest matches are first.
				krsort($titleMatches);
				krsort($urlMatches);
				krsort($akaMatches);

				// Look for 100% title matches first.
				if (isset($titleMatches[100]))
				{
					if ($this->echooutput)
						echo 'Found 100% match: "'.$titleMatches[100][0]['title'].'"'.".\n";
					return $titleMatches[100][0];
				}

				// Look for 100% url matches next.
				if (isset($urlMatches[100]))
				{
					if ($this->echooutput)
						echo 'Found 100% url match: "'.$urlMatches[100][0]['title'].'"'.".\n";
					return $urlMatches[100][0];
				}

				// Look for 100% aka matches next.
				if (isset($akaMatches[100]))
				{
					if ($this->echooutput)
						echo 'Found 100% aka match: "'.$akaMatches[100][0]['title'].'"'.".\n";
					return $akaMatches[100][0];
				}

				// No 100% matches, loop through what we got and if our next closest match is more than TvRage::MATCH_PROBABILITY % of the title lets take it.
				foreach($titleMatches as $mk=>$mv)
				{
					// Since its not 100 match if we have country info lets use that to make sure we get the right show.
					if (isset($showInfo['country']) && !empty($showInfo['country']) && !empty($mv[0]['country']))
						if (strtolower($showInfo['country']) != strtolower($mv[0]['country']))
							continue;

					if ($this->echooutput)
						echo 'Found '.$mk.'% match: "'.$titleMatches[$mk][0]['title'].'"'.".\n";
					return $titleMatches[$mk][0];
				}

				// Same as above but for akas.
				foreach($akaMatches as $ak=>$av)
				{
					if (isset($showInfo['country']) && !empty($showInfo['country']) && !empty($av[0]['country']))
						if (strtolower($showInfo['country']) != strtolower($av[0]['country']))
							continue;

					if ($this->echooutput)
						echo 'Found '.$ak.'% aka match: "'.$akaMatches[$ak][0]['title'].'"'.".\n";
					return $akaMatches[$ak][0];
				}

				if ($this->echooutput)
					echo 'No match found on TVRage, trying Trakt.'."\n";
				return false;
			}
			else
			{
				if ($this->echooutput)
					echo 'Nothing returned from tvrage.'."\n";
				return false;
			}
		}
		else
			return -1;

		if ($this->echooutput)
			echo 'No match found online.\n';
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
		$totalMatches = sizeof($ourArr)+sizeof($tvrArr);

		// Loop through each array matching again the opposite value, if they match increment!
		foreach($ourArr as $oname)
		{
			if (preg_match('/ '.preg_quote($oname, '/').' /i', ' '.$tvrName.' '))
				$numMatches++;
		}
		foreach($tvrArr as $tname)
		{
			if (preg_match('/ '.preg_quote($tname, '/').' /i', ' '.$ourName.' '))
				$numMatches++;
		}

		// Check what we're left with.
		if ($numMatches <= 0)
			return false;
		else
			$matchpct = ($numMatches/$totalMatches)*100;

		if ($matchpct >= TvRage::MATCH_PROBABILITY)
			return $matchpct;
		else
			return false;
	}

	public function cleanName($str)
	{
		$str = str_replace(array('.', '_'), ' ', $str);

		$str = str_replace(array('à','á','â','ã','ä','æ','À','Á','Â','Ã','Ä'), 'a', $str);
		$str = str_replace(array('ç','Ç'), 'c', $str);
		$str = str_replace(array('Σ','è','é','ê','ë','È','É','Ê','Ë'), 'e', $str);
		$str = str_replace(array('ì','í','î','ï','Ì','Í','Î','Ï'), 'i', $str);
		$str = str_replace(array('ò','ó','ô','õ','ö','Ò','Ó','Ô','Õ','Ö'), 'o', $str);
		$str = str_replace(array('ù','ú','û','ü','ū','Ú','Û','Ü','Ū'), 'u', $str);
		$str = str_replace('ß', 'ss', $str);

		$str = str_replace('&', 'and', $str);
		$str = preg_replace('/^(history|discovery) channel/i', '', $str);
		$str = str_replace(array('\'', ':', '!', '"', '#', '*', '’', ',', '(', ')', '?'), '', $str);
		$str = str_replace('$', 's', $str);
		$str = preg_replace('/\s{2,}/', ' ', $str);

		return trim($str);
	}

	public function parseNameEpSeason($relname)
	{
		$relname = trim(preg_replace('/EnJoY!|GOU[\.\-_ ](Der)?|SecretUsenet\scom|TcP[\.\-_ ]|usenet4ever\sinfo(\sund)?/i', '', $relname));
		$showInfo = array('name' => '', 'season' => '', 'episode' => '', 'seriesfull' => '', 'airdate' => '', 'country' => '', 'year' => '', 'cleanname' => '' );

		// S01E01-E02 and S01E01-02
		if (preg_match('/^(.*?)[\. ]s(\d{1,2})\.?e(\d{1,3})(?:\-e?|\-?e)(\d{1,3})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = array(intval($matches[3]), intval($matches[4]));
		}
		//S01E0102 - lame no delimit numbering, regex would collide if there was ever 1000 ep season.
		elseif (preg_match('/^(.*?)[\. ]s(\d{2})\.?e(\d{2})(\d{2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = array(intval($matches[3]), intval($matches[4]));
		}
		// S01E01 and S01.E01
		elseif (preg_match('/^(.*?)[\. ]s(\d{1,2})\.?e(\d{1,3})\.?/i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = intval($matches[3]);
		}
		// S01
		elseif (preg_match('/^(.*?)[\. ]s(\d{1,2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}
		// S01D1 and S1D1
		elseif (preg_match('/^(.*?)[\. ]s(\d{1,2})d\d{1}\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}
		// 1x01
		elseif (preg_match('/^(.*?)[\. ](\d{1,2})x(\d{1,3})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = intval($matches[3]);
		}
		// 2009.01.01 and 2009-01-01
		elseif (preg_match('/^(.*?)[\. ](19|20)(\d{2})[\.\-](\d{2})[\.\-](\d{2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = $matches[2].$matches[3];
			$showInfo['episode'] = $matches[4].'/'.$matches[5];
			$showInfo['airdate'] = $matches[2].$matches[3].'-'.$matches[4].'-'.$matches[5]; //yy-m-d
		}
		// 01.01.2009
		elseif (preg_match('/^(.*?)[\. ](\d{2}).(\d{2})\.(19|20)(\d{2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = $matches[4].$matches[5];
			$showInfo['episode'] = $matches[2].'/'.$matches[3];
			$showInfo['airdate'] = $matches[4].$matches[5].'-'.$matches[2].'-'.$matches[3]; //yy-m-d
		}
		// 01.01.09
		elseif (preg_match('/^(.*?)[\. ](\d{2}).(\d{2})\.(\d{2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = ($matches[4] <= 99 && $matches[4] > 15) ? '19'.$matches[4] : '20'.$matches[4];
			$showInfo['episode'] = $matches[2].'/'.$matches[3];
			$showInfo['airdate'] = $showInfo['season'].'-'.$matches[2].'-'.$matches[3]; //yy-m-d
		}
		// 2009.E01
		elseif (preg_match('/^(.*?)[\. ]20(\d{2})\.e(\d{1,3})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = '20'.$matches[2];
			$showInfo['episode'] = intval($matches[3]);
		}
		// 2009.Part1
		elseif (preg_match('/^(.*?)[\. ]20(\d{2})\.Part(\d{1,2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = '20'.$matches[2];
			$showInfo['episode'] = intval($matches[3]);
		}
		// Part1/Pt1
		elseif (preg_match('/^(.*?)[\. ](?:Part|Pt)\.?(\d{1,2})\./i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$showInfo['episode'] = intval($matches[2]);
		}
		//The.Pacific.Pt.VI.HDTV.XviD-XII / Part.IV
		elseif (preg_match('/^(.*?)[\. ](?:Part|Pt)\.?([ivx]+)/i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$epLow = strtolower($matches[2]);
			switch($epLow)
			{
				case 'i': $e = 1; break;
				case 'ii': $e = 2; break;
				case 'iii': $e = 3; break;
				case 'iv': $e = 4; break;
				case 'v': $e = 5; break;
				case 'vi': $e = 6; break;
				case 'vii': $e = 7; break;
				case 'viii': $e = 8; break;
				case 'ix': $e = 9; break;
				case 'x': $e = 10; break;
				case 'xi': $e = 11; break;
				case 'xii': $e = 12; break;
				case 'xiii': $e = 13; break;
				case 'xiv': $e = 14; break;
				case 'xv': $e = 15; break;
				case 'xvi': $e = 16; break;
				case 'xvii': $e = 17; break;
				case 'xviii': $e = 18; break;
				case 'xix': $e = 19; break;
				case 'xx': $e = 20; break;
			}
			$showInfo['episode'] = $e;
		}
		// Band.Of.Brothers.EP06.Bastogne.DVDRiP.XviD-DEiTY
		elseif (preg_match('/^(.*?)[\. ]EP?\.?(\d{1,3})/i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$showInfo['episode'] = intval($matches[2]);
		}
		// Season.1
		elseif (preg_match('/^(.*?)[\. ]Seasons?\.?(\d{1,2})/i', $relname, $matches))
		{
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}

		if (!empty($showInfo['name']))
		{
			// Country or origin matching.
			if (preg_match('/[\._ ](US|UK|AU|NZ|CA|NL|Canada|Australia|America)/', $showInfo['name'], $countryMatch))
			{
				if (strtolower($countryMatch[1]) == 'canada')
					$showInfo['country'] = 'CA';
				elseif (strtolower($countryMatch[1]) == 'australia')
					$showInfo['country'] = 'AU';
				elseif (strtolower($countryMatch[1]) == 'america')
					$showInfo['country'] = 'US';
				else
					$showInfo['country'] = strtoupper($countryMatch[1]);
			}

			// Clean show name.
			$showInfo['cleanname'] = $this->cleanName($showInfo['name']);

			// Check for dates instead of seasons.
			if (strlen($showInfo['season']) == 4)
				$showInfo['seriesfull'] = $showInfo['season']."/".$showInfo['episode'];
			else
			{
				// Get year if present (not for releases with dates as seasons).
				if (preg_match('/[\._ ](19|20)(\d{2})/i', $relname, $yearMatch))
					$showInfo['year'] = $yearMatch[1].$yearMatch[2];

				$showInfo['season'] = sprintf('S%02d', $showInfo['season']);
				// Check for multi episode release.
				if (is_array($showInfo['episode']))
				{
					$tmpArr = array();
					foreach ($showInfo['episode'] as $ep)
					{
						$tmpArr[] = sprintf('E%02d', $ep);
					}
					$showInfo['episode'] = implode('', $tmpArr);
				}
				else
					$showInfo['episode'] = sprintf('E%02d', $showInfo['episode']);

				$showInfo['seriesfull'] = $showInfo['season'].$showInfo['episode'];
			}
			$showInfo['airdate'] = (!empty($showInfo['airdate'])) ? $showInfo['airdate'].' 00:00:00' : '';
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
