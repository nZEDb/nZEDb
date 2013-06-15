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
		return $db->queryOneRow(sprintf("select * from tvrage where ID = %d", $id ));		
	}
	
	public function getByRageID($id)
	{			
		$db = new DB();
		return $db->query(sprintf("select * from tvrage where rageID = %d", $id ));		
	}
	
	public function getByTitle($title)
	{
		// check if we already have an entry for this show
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT rageID from tvrage where lower(releasetitle) = lower(%s)", $db->escapeString($title)));
		if ($res)
			return $res["rageID"];
		
		$title2 = str_replace(' and ', ' & ', $title);
		$res = $db->queryOneRow(sprintf("SELECT rageID from tvrage where lower(releasetitle) = lower(%s)", $db->escapeString($title2)));
		if ($res)
			return $res["rageID"];
		
		return false;
	}
	
	public function add($rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{	
		$releasename = str_replace(array('.','_'), array(' ',' '), $releasename);
		
		$db = new DB();
		return $db->queryInsert(sprintf("insert into tvrage (rageID, releasetitle, description, genre, country, createddate, imgdata) values (%d, %s, %s, %s, %s, now(), %s)", 
			$rageid, $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString($genre), $db->escapeString($country), $db->escapeString($imgbytes)));		
	}

	public function update($id, $rageid, $releasename, $desc, $genre, $country, $imgbytes)
	{			
		$db = new DB();
		
		if ($imgbytes != "")
			$imgbytes = sprintf(", imgdata = %s", $db->escapeString($imgbytes));
		
		$db->query(sprintf("update tvrage set rageID = %d, releasetitle = %s, description = %s, genre = %s, country = %s %s where ID = %d", 
			$rageid, $db->escapeString($releasename), $db->escapeString($desc), $db->escapeString($genre), $db->escapeString($country), $imgbytes, $id ));		
	}

	public function delete($id)
	{			
		$db = new DB();
		return $db->query(sprintf("delete from tvrage where ID = %d",$id));		
	}

	public function getRange($start, $num, $ragename="")
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
			
		$rsql = '';
		if ($ragename != "")
			$rsql .= sprintf("and tvrage.releasetitle like %s ", $db->escapeString("%".$ragename."%"));			
		
		return $db->query(sprintf(" SELECT ID, rageID, releasetitle, description, createddate from tvrage where 1=1 %s order by rageID asc".$limit, $rsql));		
	}
	
	public function getCount($ragename="")
	{			
		$db = new DB();
		
		$rsql = '';
		if ($ragename != "")
			$rsql .= sprintf("and tvrage.releasetitle like %s ", $db->escapeString("%".$ragename."%"));
			
		$res = $db->queryOneRow(sprintf("select count(ID) as num from tvrage where 1=1 %s ", $rsql));		
		return $res["num"];
	}
	
	public function getCalendar($date = "")
	{
		$db = new DB();
		if(!preg_match('/\d{4}-\d{2}-\d{2}/',$date))
			$date = date("Y-m-d");
		$sql = sprintf("SELECT * FROM tvrageepisodes WHERE DATE(airdate) = %s order by airdate asc ", $db->escapeString($date));
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
				
			$rsql .= sprintf("and tvrage.releasetitle REGEXP %s", $db->escapeString('^'.$letter));
		}	
		$tsql = '';
		if ($ragename != '')
		{
			$tsql .= sprintf("and tvrage.releasetitle like %s", $db->escapeString("%".$ragename."%"));
		}
		
		$sql = sprintf(" SELECT tvrage.ID, tvrage.rageID, tvrage.releasetitle, tvrage.genre, tvrage.country, tvrage.createddate, tvrage.prevdate, tvrage.nextdate, userseries.ID as userseriesID from tvrage left outer join userseries on userseries.userID = %d and userseries.rageID = tvrage.rageID where tvrage.rageID in (select rageid from releases) and tvrage.rageID > 0 %s %s group by tvrage.rageID order by tvrage.releasetitle asc", $uid, $rsql, $tsql);
		return $db->query($sql);		
	}
	
	public function updateSchedule()
	{
		$db = new DB();

		$countries = $db->query("select distinct(country) as country from tvrage where country != ''");
		$showsindb = $db->query("select distinct(rageid) as rageid from tvrage");
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
								$xmlSchedule[$currShowId][$tag] = array(
									'name'=> $currShowName,
									'day' => $currDay,
									'time' => $currTime,
									'day_time' => $day_time,
									'day_date' => date("Y-m-d H:i:s", $day_time),
									'title' => html_entity_decode((string)$sShow->title, ENT_QUOTES, 'UTF-8'),
									'episode' =>  html_entity_decode((string)$sShow->ep, ENT_QUOTES, 'UTF-8'),
								);
								$xmlSchedule[$currShowId]['showname'] = $currShowName;
							}
							if($sShow->ep == "01x01")
								{
								// Only add it here, no point adding it to tvrage aswell
								// that will automatically happen when an ep gets posted
								$showarray[] = $sShow->sid;
								}
							if(in_array($currShowId,$showarray)) //only stick current shows and new shows in there
							{
								$showname = $db->escapeString($currShowName);
								$title = $db->escapeString($sShow->title);
								$fullep = $db->escapeString($sShow->ep);
								$link = $db->escapeString($sShow->link);
								$airdate = $db->escapeString(date("Y-m-d H:i:s", $day_time));
								$sql = sprintf("INSERT into tvrageepisodes (rageID,showtitle,fullep,airdate,link,eptitle) VALUES (%d,%s,%s,%s,%s,%s)
										ON DUPLICATE KEY UPDATE airdate = %s, link = %s ,eptitle = %s, showtitle = %s",
										$sShow->sid,$showname,$fullep,$airdate,$link,$title,$airdate,$link,$title,$showname);
								$db->queryInsert($sql);
							}
						}
					}
				}
				// update series info
				foreach ($xmlSchedule as $showId=>$epInfo) 
				{
					$res = $db->query(sprintf("select *, UNIX_TIMESTAMP(nextdate) as nextDateU, UNIX_TIMESTAMP(DATE(nextdate)) as nextDateDay from tvrage where rageID = %d", $showId));
					if (sizeof($res) > 0) 
					{
						foreach ($res as $arr) 
						{
							$prev_ep = $next_ep = "";
							$query = array();
							
							// previous episode
							if (isset($epInfo['prev']) && $epInfo['prev']['episode'] != '') 
							{
								$prev_ep = $epInfo['prev']['episode'].', "'.$epInfo['prev']['title'].'"';
								$query[] = sprintf("prevdate = FROM_UNIXTIME(%s), previnfo = %s", $epInfo['prev']['day_time'], $db->escapeString($prev_ep));
							}
	
							// next episode
							if (isset($epInfo['next']) && $epInfo['next']['episode'] != '') 
							{
								if ($prev_ep == "" && $arr['nextinfo'] != '' && $epInfo['next']['day_time'] > $arr['nextDateU'] && $arr['nextDateDay'] < $yesterday) 
								{
									$db->query(sprintf("update tvrage set prevdate = nextdate, previnfo = nextinfo where ID = %d", $arr['ID']));
									$prev_ep = "SWAPPED with: ".$arr['nextInfo']." - ".date("r", $arr['nextDateU']);
								}
								$next_ep = $epInfo['next']['episode'].', "'.$epInfo['next']['title'].'"';
								$query[] = sprintf("nextdate = FROM_UNIXTIME(%s), nextinfo = %s", $epInfo['next']['day_time'], $db->escapeString($next_ep));
							} 
							else 
							{
								$query[] = "nextdate = null, nextinfo = null";
							}
							
							// output
							if ($this->echooutput)
							{
								echo $epInfo['showname']." (".$showId."):\n";
								echo "Previous: {$prev_ep} - ".(isset($epInfo['prev']['day_time']) ? date("r",$epInfo['prev']['day_time']) : "").".\n";
								echo "Next: {$next_ep} - ".(isset($epInfo['next']['day_time']) ? date("r",$epInfo['next']['day_time']) : "").".\n";
							}
							
							// update info
							if (count($query) > 0)
							{
								$sql = join(", ", $query);
								$sql = sprintf("update tvrage set {$sql} where ID = %d", $arr['ID']);
								$db->query($sql);
							}
						}
					}
				} // end update series info
			}
			else
			{
				// no response from tvrage
				if ($this->echooutput)
					echo "Schedule not found.\n";
			}
		} // end foreach country
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
			//description
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
			// image
			preg_match("@src=[\"'](http://images.tvrage.com/shows.*?)[\"']@i", $page, $matches);
			if (isset($matches[1])) {
				$result['imgurl'] = $matches[1];
			}
		}
		return $result;
	}
	
	public function getRageInfoFromService($rageid)
	{
		$result = array('genres'=>'', 'country'=>'', 'showid'=>$rageid);
		$xml = getUrl($this->xmlFullShowInfoUrl.$rageid); //full search gives us the akas
		if ($xml !== false)
		{
			$xmlObj = simplexml_load_string($xml);
			$arrXml = objectsIntoArray($xmlObj);

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
	
	public function updateEpInfo($show, $relid)
	{
		$db = new DB();
		
		if ($this->echooutput)
			echo "TV series: ".$show['name']."-".$show['seriesfull'].(($show['year']!='')?' '.$show['year']:'').(($show['country']!='')?' ['.$show['country'].']':'').".\n";
		
		$tvairdate = (!empty($show['airdate'])) ? $db->escapestring($show['airdate']) : "null";
		
		$db->query(sprintf("update releases set seriesfull = %s, season = %s, episode = %s, tvairdate=%s where ID = %d", 
					$db->escapeString($show['seriesfull']), $db->escapeString($show['season']), $db->escapeString($show['episode']), $tvairdate, $relid));
	}
	
	public function updateRageInfo($rageid, $show, $tvrShow, $relid)
	{
		$db = new DB();
		
		// try and get the episode specific info from tvrage
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false)
		{
			$tvairdate = (!empty($epinfo['airdate'])) ? $db->escapeString($epinfo['airdate']) : "null";
			$tvtitle = (!empty($epinfo['title'])) ? $db->escapeString($epinfo['title']) : "null";

			$db->query(sprintf("update releases set tvtitle=trim(%s), tvairdate=%s, rageID = %d where ID = %d", $tvtitle, $tvairdate, $tvrShow['showid'], $relid));
		} else {
			$db->query(sprintf("update releases set rageID = %d where ID = %d", $tvrShow['showid'], $relid));
		}
		
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
		
		// try and get the episode specific info from tvrage
		$epinfo = $this->getEpisodeInfo($rageid, $show['season'], $show['episode']);
		if ($epinfo !== false)
		{
			$tvairdate = (!empty($epinfo['airdate'])) ? $db->escapeString($epinfo['airdate']) : "null";
			$tvtitle = (!empty($epinfo['title'])) ? $db->escapeString($epinfo['title']) : "null";

			$db->query(sprintf("update releases set tvtitle=trim(%s), tvairdate=%s, rageID = %d where ID = %d", $tvtitle, $tvairdate, $traktArray['show']['tvrage_id'], $relid));
		} else {
			$db->query(sprintf("update releases set rageID = %d where ID = %d", $traktArray['show']['tvrage_id'], $relid));
		}
		
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
	
	public function processTvReleases($threads=1, $lookupTvRage=true)
	{
		$ret = 0;
		$db = new DB();
		$trakt = new Trakttv();
		$site = new Sites();
		$threads--;

		// get all releases without a rageid which are in a tv category.
		$result = $db->queryDirect(sprintf("SELECT searchname, ID from releases where rageID = -1 and nzbstatus = 1 and categoryID in ( select ID from category where parentID = %d ) order by postdate desc limit %d,%d", Category::CAT_PARENT_TV, floor(($this->rageqty) * ($threads * 1.5)), $this->rageqty));
		
		if ($this->echooutput)
		{
			$tvremain = $db->getNumRows($result);
			if ($tvremain > 0)
				echo "Processing TV for ".$tvremain." release(s).\n";
		}
			
		while ($arr = $db->fetchAssoc($result)) 
		{
			$show = $this->parseNameEpSeason($arr['searchname']);			
			if (is_array($show) && $show['name'] != '')
			{	
				// update release with season, ep, and airdate info (if available) from releasetitle
				$this->updateEpInfo($show, $arr['ID']);
								
				// find the rageID
				$id = $this->getByTitle($show['cleanname']);
				
				if ($id === false && $lookupTvRage)
				{
					// if it doesnt exist locally and lookups are allowed lets try to get it
					if ($this->echooutput)
						echo 'TVRage ID for "'.$show['cleanname'].'" not found in local db, checking web...'."\n";
					
					$tvrShow = $this->getRageMatch($show);
					if ($tvrShow !== false && is_array($tvrShow))
					{
						// get all tv info and add show
						$this->updateRageInfo($tvrShow['showid'], $show, $tvrShow, $arr['ID']);
					}
					elseif ($tvrShow === false)
					{
						// If tvrage fails, try trakt
						$traktArray = $trakt->traktTVSEsummary($show['name'], $show['season'], $show['episode']);
						if ($traktArray !== false)
						{
							if(isset($traktArray['show']['tvrage_id']) && $traktArray['show']['tvrage_id'] !== 0)
							{
								if ($this->echooutput)
									echo 'Found TVRage ID on trakt :'.$traktArray['show']['tvrage_id']."\n";
								$this->updateRageInfoTrakt($traktArray['show']['tvrage_id'], $show, $traktArray, $arr['ID']);
							}
							else
							{
								// no match
								//add to tvrage with rageID = -2 and $show['cleanname'] title only
								$this->add(-2, $show['cleanname'], '', '', '', '');
							}
						}
						else
						{
							// no match
							//add to tvrage with rageID = -2 and $show['cleanname'] title only
							$this->add(-2, $show['cleanname'], '', '', '', '');
						}
					}
					else
					{
						// $tvrShow probably equals -1 but we'll do this as a catchall instead of a specific elseif

						//skip because we couldnt connect to tvrage.com
					}

				}
				elseif ($id > 0)
				{
					$tvairdate = (isset($show['airdate']) && !empty($show['airdate'])) ? $db->escapeString($show['airdate']) : "null";
					$tvtitle = "null";
					
					if ($lookupTvRage)
					{
						$epinfo = $this->getEpisodeInfo($id, $show['season'], $show['episode']);
						if ($epinfo !== false)
						{
							if (!empty($epinfo['airdate']))
								$tvairdate = $db->escapeString($epinfo['airdate']);
							
							if (!empty($epinfo['title']))
								$tvtitle = $db->escapeString($epinfo['title']);
						}
					}
					$db->query(sprintf("update releases set tvtitle=trim(%s), tvairdate=%s, rageID = %d where ID = %d", $tvtitle, $tvairdate, $id, $arr["ID"]));
				}
				else
				{
					// cant find rageid, so set rageid to n/a 
					$db->query(sprintf("update releases set rageID = -2 where ID = %d", $arr["ID"]));
				}
			}
			else
			{
				// not a tv episode, so set rageid to n/a
				$db->query(sprintf("update releases set rageID = -2 where ID = %d", $arr["ID"]));
			}
			$ret++;
		}
		
		return $ret;
	}
	
	public function getRageMatch($showInfo)
	{
		$title = $showInfo['cleanname'];
		
		$xml = getUrl($this->xmlFullSearchUrl.urlencode(strtolower($title))); //full search gives us the akas
		if ($xml !== false)
		{
			$xmlObj = simplexml_load_string($xml);
			$arrXml = objectsIntoArray($xmlObj);
			
			if (isset($arrXml['show']) && is_array($arrXml['show']))
			{
				// we got a valid xml response
				$titleMatches = array();
				$urlMatches = array();
				$akaMatches = array();
				
				if (isset($arrXml['show']['showid']))
				{
					// we got exactly 1 match so lets convert it to an array so we can use it in the logic below
					$newArr = array();
					$newArr[] = $arrXml['show'];
					unset($arrXml);
					$arrXml['show'] = $newArr;
				}
				
				foreach ($arrXml['show'] as $arr)
				{	
					$titlepct = $urlpct = $akapct = 0;
				
					// get a match percentage based on our name and the name returned from tvr				
					$titlepct = $this->checkMatch($title, $arr['name']);
					if ($titlepct !== false)
						$titleMatches[$titlepct][] = array('title'=>$arr['name'], 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
					
					// get a match percentage based on our name and the url returned from tvr	
					if (isset($arr['link']) && preg_match('/tvrage\.com\/((?!shows)[^\/]*)$/i', $arr['link'], $tvrlink))
					{
						$urltitle = str_replace('_', ' ', $tvrlink[1]);
						$urlpct = $this->checkMatch($title, $urltitle);
						if ($urlpct !== false)
							$urlMatches[$urlpct][] = array('title'=>$urltitle, 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
					}
					
					// check if there are any akas for this result and get a match percentage for them too
					if (isset($arr['akas']))
					{
						if (is_array($arr['akas']['aka']))
						{
							// multuple akas
							foreach($arr['akas']['aka'] as $aka)
							{
								$akapct = $this->checkMatch($title, $aka);
								if ($akapct !== false)
									$akaMatches[$akapct][] = array('title'=>$aka, 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
							}
						} else {
							// one aka
							$akapct = $this->checkMatch($title, $arr['akas']['aka']);
							if ($akapct !== false)
								$akaMatches[$akapct][] = array('title'=>$arr['akas']['aka'], 'showid'=>$arr['showid'], 'country'=>$arr['country'], 'genres'=>$arr['genres'], 'tvr'=>$arr);
						}
					}
				
				}
				
				// reverse sort our matches so highest matches are first
				krsort($titleMatches);
				krsort($urlMatches);
				krsort($akaMatches);
				
				//print_r($titleMatches);
				//print_r($urlMatches);
				//print_r($akaMatches)
				
				// look for 100% title matches first
				if (isset($titleMatches[100]))
				{
					if ($this->echooutput)
						echo 'Found 100% match: "'.$titleMatches[100][0]['title'].'"'.".\n";
					return $titleMatches[100][0];
				}
				
				// look for 100% url matches next
				if (isset($urlMatches[100]))
				{
					if ($this->echooutput)
						echo 'Found 100% url match: "'.$urlMatches[100][0]['title'].'"'.".\n";
					return $urlMatches[100][0];
				}
				
				// look for 100% aka matches next
				if (isset($akaMatches[100]))
				{
					if ($this->echooutput)
						echo 'Found 100% aka match: "'.$akaMatches[100][0]['title'].'"'.".\n";
					return $akaMatches[100][0];
				}
				
				// no 100% matches, loop through what we got and if our next closest match is more than TvRage::MATCH_PROBABILITY % of the title lets take it
				foreach($titleMatches as $mk=>$mv)
				{
					// since its not 100 match if we have country info lets use that to make sure we get the right show
					if (isset($showInfo['country']) && !empty($showInfo['country']) && !empty($mv[0]['country']))
						if (strtolower($showInfo['country']) != strtolower($mv[0]['country']))
							continue;
					
					if ($this->echooutput)
						echo 'Found '.$mk.'% match: "'.$titleMatches[$mk][0]['title'].'"'.".\n";
					return $titleMatches[$mk][0];
				}
				
				// same as above but for akas
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
				
			} else {
				if ($this->echooutput)
					echo 'Nothing returned from tvrage.'."\n";
				return false;
			}
	
		} else {
			if ($this->echooutput)
			return -1;
		}
		
		if ($this->echooutput)
			echo 'No match found online.\n';
		return false;
	}
	
	public function checkMatch($ourName, $tvrName)
	{
		// clean up name ($ourName is already clean)
		$tvrName = $this->cleanName($tvrName);
		$tvrName = preg_replace('/ of /i', '', $tvrName);
		$ourName = preg_replace('/ of /i', '', $ourName);
		
		// create our arrays
		$ourArr = explode(' ', $ourName);
		$tvrArr = explode(' ', $tvrName);
		
		// set our match counts
		$numMatches = 0;
		$totalMatches = sizeof($ourArr)+sizeof($tvrArr);
		
		// loop through each array matching again the opposite value, if they match increment!
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
		
		// check what we're left with
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
		$showInfo = array(
			'name' => '',
			'season' => '',
			'episode' => '',
			'seriesfull' => '',
			'airdate' => '',
			'country' => '',
			'year' => '',
			'cleanname' => ''
		);
		
		//S01E01-E02
		//S01E01-02
		if (preg_match('/^(.*?)[\. ]s(\d{1,2})\.?e(\d{1,3})(?:\-e?|\-?e)(\d{1,3})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = array(intval($matches[3]), intval($matches[4]));
		//S01E0102 - lame no delimit numbering, regex would collide if there was ever 1000 ep season
		} elseif (preg_match('/^(.*?)[\. ]s(\d{2})\.?e(\d{2})(\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = array(intval($matches[3]), intval($matches[4]));
		//S01E01
		//S01.E01
		} elseif (preg_match('/^(.*?)[\. ]s(\d{1,2})\.?e(\d{1,3})\.?/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = intval($matches[3]);
		//S01
		} elseif (preg_match('/^(.*?)[\. ]s(\d{1,2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		//S01D1
		//S1D1
		} elseif (preg_match('/^(.*?)[\. ]s(\d{1,2})d\d{1}\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		//1x01
		} elseif (preg_match('/^(.*?)[\. ](\d{1,2})x(\d{1,3})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = intval($matches[3]);
		//2009.01.01
		//2009-01-01
		} elseif (preg_match('/^(.*?)[\. ](19|20)(\d{2})[\.\-](\d{2})[\.\-](\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = $matches[2].$matches[3];
			$showInfo['episode'] = $matches[4].'/'.$matches[5];
			$showInfo['airdate'] = $matches[2].$matches[3].'-'.$matches[4].'-'.$matches[5]; //yy-m-d
		//01.01.2009
		} elseif (preg_match('/^(.*?)[\. ](\d{2}).(\d{2})\.(19|20)(\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = $matches[4].$matches[5];
			$showInfo['episode'] = $matches[2].'/'.$matches[3];
			$showInfo['airdate'] = $matches[4].$matches[5].'-'.$matches[2].'-'.$matches[3]; //yy-m-d
		//01.01.09
		} elseif (preg_match('/^(.*?)[\. ](\d{2}).(\d{2})\.(\d{2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = ($matches[4] <= 99 && $matches[4] > 15) ? '19'.$matches[4] : '20'.$matches[4];
			$showInfo['episode'] = $matches[2].'/'.$matches[3];
			$showInfo['airdate'] = $showInfo['season'].'-'.$matches[2].'-'.$matches[3]; //yy-m-d
		//2009.E01
		} elseif (preg_match('/^(.*?)[\. ]20(\d{2})\.e(\d{1,3})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = '20'.$matches[2];
			$showInfo['episode'] = intval($matches[3]);
		//2009.Part1
		} elseif (preg_match('/^(.*?)[\. ]20(\d{2})\.Part(\d{1,2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = '20'.$matches[2];
			$showInfo['episode'] = intval($matches[3]);
		//Part1/Pt1
		} elseif (preg_match('/^(.*?)[\. ](?:Part|Pt)\.?(\d{1,2})\./i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$showInfo['episode'] = intval($matches[2]);
		//The.Pacific.Pt.VI.HDTV.XviD-XII / Part.IV
		} elseif (preg_match('/^(.*?)[\. ](?:Part|Pt)\.?([ivx]+)/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$epLow = strtolower($matches[2]);
			switch($epLow) {
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
		//Band.Of.Brothers.EP06.Bastogne.DVDRiP.XviD-DEiTY
		} elseif (preg_match('/^(.*?)[\. ]EP?\.?(\d{1,3})/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = 1;
			$showInfo['episode'] = intval($matches[2]);
		//Season.1
		} elseif (preg_match('/^(.*?)[\. ]Seasons?\.?(\d{1,2})/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
			$showInfo['season'] = intval($matches[2]);
			$showInfo['episode'] = 'all';
		}
		
		if (!empty($showInfo['name'])) {
			//country or origin matching
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
			
			//clean show name
			$showInfo['cleanname'] = $this->cleanName($showInfo['name']);
		
			//check for dates instead of seasons
			if (strlen($showInfo['season']) == 4) {
				$showInfo['seriesfull'] = $showInfo['season']."/".$showInfo['episode'];
			} else {
				//get year if present (not for releases with dates as seasons)
				if (preg_match('/[\._ ](19|20)(\d{2})/i', $relname, $yearMatch))
					$showInfo['year'] = $yearMatch[1].$yearMatch[2];
				
				$showInfo['season'] = sprintf('S%02d', $showInfo['season']);
				//check for multi episode release
				if (is_array($showInfo['episode'])) {
					$tmpArr = array();
					foreach ($showInfo['episode'] as $ep) {
						$tmpArr[] = sprintf('E%02d', $ep);
					}
					$showInfo['episode'] = implode('', $tmpArr);
				} else {
					$showInfo['episode'] = sprintf('E%02d', $showInfo['episode']);
				}
				$showInfo['seriesfull'] = $showInfo['season'].$showInfo['episode'];
			}
			$showInfo['airdate'] = (!empty($showInfo['airdate'])) ? $showInfo['airdate'].' 00:00:00' : '';
			return $showInfo;
		}
		
		return false;
	}
	
	public function getGenres()
	{
		return array(
			'Action',
			'Adult/Porn',
			'Adventure',
			'Anthology',
			'Arts & Crafts',
			'Automobiles',
			'Buy, Sell & Trade',
			'Celebrities',
			'Children',
			'Cinema/Theatre',
			'Comedy',
			'Cooking/Food',
			'Crime',
			'Current Events',
			'Dance',
			'Debate',
			'Design/Decorating',
			'Discovery/Science',
			'Drama',
			'Educational',
			'Family',
			'Fantasy',
			'Fashion/Make-up',
			'Financial/Business',
			'Fitness',
			'Garden/Landscape',
			'History',
			'Horror/Supernatural',
			'Housing/Building',
			'How To/Do It Yourself',
			'Interview',
			'Lifestyle',
			'Literature',
			'Medical',
			'Military/War',
			'Music',
			'Mystery',
			'Pets/Animals',
			'Politics',
			'Puppets',
			'Religion',
			'Romance/Dating',
			'Sci-Fi',
			'Sketch/Improv',
			'Soaps',
			'Sports',
			'Super Heroes',
			'Talent',
			'Tech/Gaming',
			'Teens',
			'Thriller',
			'Travel',
			'Western',
			'Wildlife'
		);
	}
	
}