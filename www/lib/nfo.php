<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/nntp.php");
require_once(WWW_DIR."lib/movie.php");
require_once(WWW_DIR."lib/tvrage.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nzbcontents.php");
require_once(WWW_DIR."lib/site.php");

class Nfo 
{
	function Nfo($echooutput=false) 
	{
		$s = new Sites();
		$site = $s->get();
		$this->nzbs = (!empty($site->maxnfoprocessed)) ? $site->maxnfoprocessed : 100;
		$this->maxsize = (!empty($site->maxsizetopostprocess)) ? $site->maxsizetopostprocess : 100;
		$this->echooutput = $echooutput;
	}
	
	public function addReleaseNfo($relid)
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
		preg_match('/(?:imdb.*?)?(?:tt|Title\?)(\d{5,7})/i', $str, $matches);
		if (isset($matches[1]) && !empty($matches[1]))
		{
			return trim($matches[1]);
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
	
	public function processNfoFiles($threads=1, $processImdb=1, $processTvrage=1)
	{
		$ret = 0;
		$db = new DB();
		$nntp = new Nntp();
		$groups = new Groups();
		$site = new Sites;
		$nzbcontents = new NZBcontents($this->echooutput);
		$threads--;

		$i = -1;
		$nfocount = 0;
		while ((($nfocount) != $this->nzbs) && ($i >= -6))
		{
			$res = $db->queryDirect(sprintf("SELECT ID, guid, groupID, name FROM releases WHERE nfostatus between %d and -1 and nzbstatus = 1 and size < %s order by postdate desc limit %d,%d", $i, $this->maxsize*1073741824, floor(($this->nzbs) * ($threads * 1.5)), $this->nzbs));
			$nfocount = $db->getNumRows($res);
			$i--;
		}

		if ($nfocount > 0)
		{
			if ($this->echooutput)
//				if ($nfocount > 0)
//					echo "Processing ".$nfocount." NFO(s), starting at ".floor(($this->nzbs) * $threads * 1.5)." * = hidden NFO, + = NFO, - = no NFO, f = download failed.\n";

			$nntp->doConnect();
			$movie = new Movie($this->echooutput);
			while ($arr = $db->fetchAssoc($res))
			{
				$guid = $arr['guid'];
				$relID = $arr['ID'];
				$groupID = $arr['groupID'];
				$fetchedBinary = $nzbcontents->getNFOfromNZB($guid, $relID, $groupID, $nntp);
				if ($this->echooutput)
					echo "\nNFO Processing on ".$arr['name'];
				if ($fetchedBinary !== false)
				{
					//insert nfo into database
					$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($fetchedBinary), $arr["ID"]));
					$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $arr["ID"]));
					$ret++;

					$imdbId = $movie->domovieupdate($fetchedBinary, 'nfo', $arr["ID"], $db, $processImdb);

					$rageId = $this->parseRageId($fetchedBinary);
					if ($rageId !== false)
					{
						//if set scan for tvrage info
						if ($processTvrage == 1)
						{
							$tvrage = new Tvrage();
							$show = $tvrage->parseNameEpSeason($arr['name']);
							if (is_array($show) && $show['name'] != '')
							{
								// update release with season, ep, and airdate info (if available) from releasetitle
								$tvrage->updateEpInfo($show, $arr['ID']);

								$rid = $tvrage->getByRageID($rageId);
								if (!$rid)
								{
									$tvrShow = $tvrage->getRageInfoFromService($rageId);
									$tvrage->updateRageInfo($rageId, $show, $tvrShow, $arr['ID']);
								}
							}
						}
					}
				}
			}
			$nntp->doQuit();
		}

		//remove nfo that we cant fetch after 5 attempts
		$relres = $db->queryDirect("Select ID from releases where nfostatus <= -6");
		while ($relrow = $db->fetchAssoc($relres))
		{
			$db->query(sprintf("DELETE FROM releasenfo WHERE nfo IS NULL and releaseID = %d", $relrow['ID']));
		}

		if ($this->echooutput)
		{
			if ($nfocount > 0)
				echo "\n";
			if ($ret > 0)
				echo $ret." NFO file(s) found/processed.\n";
		}

		return $ret;
	}
}
?>
