<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/movie.php");
require_once(WWW_DIR."/lib/tvrage.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/nzbcontents.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."lib/rarinfo/par2info.php");

/*
 * Class for handling fetching/storing of NFO files.
 */
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
		return $db->queryInsert(sprintf("INSERT IGNORE INTO releasenfo (releaseID) VALUE (%d)", $relid));
	}

	public function deleteReleaseNfo($relid)
	{
		$db = new DB();
		return $db->query(sprintf("delete from releasenfo where releaseID = %d", $relid));
	}

	// Find an IMDB ID in a NFO file.
	public function parseImdb($str)
	{
		preg_match('/(?:imdb.*?)?(?:tt|Title\?)(\d{5,7})/i', $str, $matches);
		if (isset($matches[1]) && !empty($matches[1]))
		{
			return trim($matches[1]);
		}
		return false;
	}

	// Find a TVRage ID in a NFO.
	public function parseRageId($str)
	{
		preg_match('/tvrage\.com\/shows\/id-(\d{1,6})/i', $str, $matches);
		if (isset($matches[1]))
		{
			return trim($matches[1]);
		}
		return false;
	}

	// Confirm that the .nfo file is not something else.
	public function isNFO($possibleNFO)
	{
		$ok = false;
		if ($possibleNFO !== false)
		{
			if (!preg_match('/(<?xml|;\s*Generated\sby.+SF\w|^\s*PAR|\.[a-z0-9]{2,7}\s[a-z0-9]{8}|^\s*RAR|\A.{0,10}(JFIF|matroska|ftyp|ID3))/i', $possibleNFO))
			{
				if (strlen($possibleNFO) < 45 * 1024)
				{
					// exif_imagetype needs a minimum size or else it doesn't work.
					if (strlen($possibleNFO) > 15)
					{
						// Check if it's a picture - EXIF.
						if (@exif_imagetype($possibleNFO) == false)
						{
							// Check if it's a picture - JFIF.
							if ($this->check_JFIF($possibleNFO) == false)
							{
								// Check if it's a par2.
								$par2info = new Par2Info();
								$par2info->setData($possibleNFO);
								if ($par2info->error)
								{
									$ok = true;
								}
							}
						}
					}
				}
			}
		}
		return $ok;
	}

	//	Check if the possible NFO is a JFIF.
	function check_JFIF($filename)
	{
		$fp = @fopen($filename, 'r');
		if ($fp)
		{
			// JFIF often (but not always) starts at offset 6.
			if (fseek($fp, 6) == 0)
			{
				// JFIF header is 16 bytes.
				if (($bytes = fread($fp, 16)) !== false)
				{
					// Make sure it is JFIF header.
					if (substr($bytes, 0, 4) == "JFIF")
						return true;
					else
						return false;
				}
			}
		}
	}

	// Adds an NFO found from predb, rar, zip etc...
	public function addAlternateNfo($db, $nfo, $release)
	{
		if ($this->isNFO($nfo) && $release["ID"] > 0)
		{
			$this->addReleaseNfo($release["ID"]);
			$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($nfo), $release["ID"]));
			$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $release["ID"]));
			if ($release["completion"] == 0)
			{
				$nzbcontents = new NZBcontents($this->echooutput);
				$nzbcontents->NZBcompletion($release["guid"], $release["ID"], $release["groupID"]);
			}
			return true;
		}
		else
			return false;
	}

	// Loop through releases, look for NFO's in the NZB file.
	public function processNfoFiles($releaseToWork = '', $processImdb=1, $processTvrage=1)
	{
		$db = new DB();
		$s = new Sites();
		$site = $s->get();
		$nntp = new Nntp();
		$site->alternate_nntp == "1" ? $nntp->doConnect_A() : $nntp->doConnect();
		$groups = new Groups();
		$nzbcontents = new NZBcontents($this->echooutput);
		$nfocount = $ret = 0;

		if ($releaseToWork == '')
		{
			$i = -1;
			while (($nfocount != $this->nzbs) && ($i >= -6))
			{
				$res = $db->query(sprintf("SELECT ID, guid, groupID, name FROM releases WHERE nfostatus between %d and -1 and nzbstatus = 1 and size < %s order by postdate desc limit %d", $i, $this->maxsize*1073741824, $this->nzbs));
				$nfocount = count($res);
				$i--;
			}
		}
		else
		{
			$res = 0;
			$pieces = explode("           =+=            ", $releaseToWork);
			$res = array(array('ID' => $pieces[0], 'guid' => $pieces[1], 'groupID' => $pieces[2], 'name' => $pieces[3]));
			$nfocount = 1;
		}

		if ($nfocount > 0)
		{
			if ($this->echooutput)
				if ($releaseToWork == '')
					echo "Processing ".$nfocount." NFO(s), starting at ".$this->nzbs." * = hidden NFO, + = NFO, - = no NFO, f = download failed.\n";

			$movie = new Movie($this->echooutput);
			foreach ($res as $arr)
			{
				$site->alternate_nntp == "1" ? $nntp->doConnect_A() : $nntp->doConnect();
				$fetchedBinary = $nzbcontents->getNFOfromNZB($arr['guid'], $arr['ID'], $arr['groupID'], $nntp);
				if (PEAR::isError($fetchedBinary))
				{
					$groupName = $groups->getByNameByID($arr['groupID']);
					$nntp = new Nntp;
					$site->alternate_nntp == "1" ? $nntp->doConnect_A() : $nntp->doConnect();
					$data = $nntp->selectGroup($groupName);
					$fetchedBinary = $nzbcontents->getNFOfromNZB($arr['guid'], $arr['ID'], $arr['groupID'], $nntp);
					if (PEAR::isError($fetchedBinary))
					{
						echo "\n\nError {$fetchedBinary->code}: {$fetchedBinary->message}\n\n";
						return;
					}
				}

				if ($fetchedBinary !== false)
				{
					//insert nfo into database
					$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($fetchedBinary), $arr["ID"]));
					$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $arr["ID"]));
					$ret++;

					$imdbId = $movie->domovieupdate($fetchedBinary, 'nfo', $arr["ID"], $db, $processImdb);

					// If set scan for tvrage info.
					if ($processTvrage == 1)
					{
						$rageId = $this->parseRageId($fetchedBinary);
						if ($rageId !== false)
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
				$nntp->doQuit();
			}
		}

		// Remove nfo that we cant fetch after 5 attempts.
		if ($releaseToWork == '')
		{
			$relres = $db->queryDirect("Select ID from releases where nfostatus <= -6");
			while ($relrow = $db->fetchAssoc($relres))
			{
				$db->query(sprintf("DELETE FROM releasenfo WHERE nfo IS NULL and releaseID = %d", $relrow['ID']));
			}

			if ($this->echooutput)
			{
				if ($nfocount > 0 && $releaseToWork == '')
					echo "\n";
				if ($ret > 0 && $releaseToWork == '')
					echo $ret." NFO file(s) found/processed.\n";
			}
			return $ret;
		}
	}
}
