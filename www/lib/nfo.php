<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/movie.php");
require_once(WWW_DIR."/lib/tvrage.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/nzbcontents.php");

class Nfo 
{
	function Nfo($echooutput=false) 
	{
		$this->echooutput = $echooutput;
	}
	
	public function determineReleaseNfo($relid)
	{
		$nfos = array();
		$db = new DB();
		$cresult = $db->queryDirect(sprintf("select ID from collections where releaseID = %d", $relid));
		while ($crow = mysql_fetch_assoc($cresult))
		{
			$colID = $crow['ID'];
			$bresult = $db->queryDirect(sprintf("select ID, name from binaries where collectionID = %d", $colID));
			while ($brow = mysql_fetch_assoc($bresult)) 
				if (preg_match('/.*\.nfo[ "\)\]\-]?/i', $brow['name'])) 
					$nfos[$brow['name']] = $brow;
		}
		ksort($nfos);
		return (is_array($nfos) && !empty($nfos)) ? array_shift($nfos) : false;
	}
	
	public function addReleaseNfo($relid, $binid)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO releasenfo (releaseID) VALUE (%d)", $relid));		
	}
	
	public function deleteReleaseNfo($relid)
	{
		$db = new DB();
		return $db->query(sprintf("delete from releasenfo where releaseID = %d", $relid));		
	}
	
	public function parseImdb($str) 
	{
		preg_match('/imdb.*?(tt|Title\?)(\d{7})/i', $str, $matches);
		if (isset($matches[2]) && !empty($matches[2])) 
		{
			return trim($matches[2]);
		}
		return false;
	}
	
	public function parseRageId($str) 
	{
		preg_match('/tvrage\.com\/shows\/id-(\d{1,6})/i', $str, $matches);
		if (isset($matches[1])) 
		{
			return trim($matches[1]);
		}
		return false;
	}
	
	public function processNfoFiles($processImdb=1, $processTvrage=1)
	{
		$ret = 0;
		$db = new DB();
		$nntp = new Nntp();
		$groups = new Groups();
		$nzbcontents = new NZBcontents();
		
		$res = $db->queryDirect("SELECT rn.ID, r.guid, r.groupID, rn.releaseID FROM releasenfo rn left outer join releases r ON r.ID = rn.releaseID WHERE rn.nfo IS NULL AND r.nfostatus between -6 and -1 order by adddate asc limit 0,50");
		if (mysql_num_rows($res) > 0)
		{	
			if ($this->echooutput)
				echo "Processing ".mysql_num_rows($res)." NFO's.\n";
		
			$nntp->doConnect();
			while ($arr = mysql_fetch_assoc($res))
			{
				$guid = $arr['guid'];
				$messageid = $nzbcontents->getNFOfromNZB($guid);
				$groupID = $arr['groupID'];
				$groupName = $groups->getByNameByID($groupID);
				$fetchedBinary = $nntp->getMessage($groupName, $messageid);
				
				if ($fetchedBinary !== false) 
				{
					//insert nfo into database
					$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE ID = %d", $db->escapeString($fetchedBinary), $arr["ID"]));
					$ret++;
					
					$imdbId = $this->parseImdb($fetchedBinary);
					if ($imdbId !== false) 
					{
						//update release with imdb id
						$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["releaseID"]));
						
						//if set scan for imdb info
						if ($processImdb == 1)
						{
							$movie = new Movie($this->echooutput);
							//check for existing movie entry
							$movCheck = $movie->getMovieInfo($imdbId);
							if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
							{
								$movieId = $movie->updateMovieInfo($imdbId);
							}
						}
					}
					
					$rageId = $this->parseRageId($fetchedBinary);
					if ($rageId !== false)
					{	
						//if set scan for tvrage info
						if ($processTvrage == 1)
						{
							$tvrage = new Tvrage($this->echooutput);
							$show = $tvrage->parseNameEpSeason($arr['searchname']);	
							if (is_array($show) && $show['name'] != '')
							{	
								// update release with season, ep, and airdate info (if available) from releasetitle
								$tvrage->updateEpInfo($show, $arr['releaseID']);
								
								$rid = $tvrage->getByRageID($rageId);
								if (!$rid)
								{
									$tvrShow = $tvrage->getRageInfoFromService($rageId);
									$tvrage->updateRageInfo($rageId, $show, $tvrShow, $arr['releaseID']);
								}
							}
						}
					}
				} 
				else 
				{
					//nfo download failed, increment attempts
					$db->query(sprintf("UPDATE releases SET nfostatus = nfostatus-1 WHERE ID = %d", $arr["releaseID"]));
				}
				
				if ($ret != 0 && $this->echooutput && ($ret % 5 == 0))
					echo $ret."..";
				
			}
			$nntp->doQuit();
		}
		
		//remove nfo that we cant fetch after 5 attempts
		$relres = $db->queryDirect("Select ID from releases where nfostatus <= -6");
		while ($relrow = mysql_fetch_assoc($relres))
		{
			$db->query(sprintf("DELETE FROM releasenfo WHERE nfo IS NULL and releaseID = %d", $relrow['ID']));
		}
		
		if ($this->echooutput)
			echo "\n".$ret." NFO files processed\n";
		
		return $ret;
	}
}
?>
