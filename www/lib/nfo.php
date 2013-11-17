<?php
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'movie.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'nzbcontents.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'tvrage.php';
require_once nZEDb_LIB . 'ColorCLI.php';

/*
 * Class for handling fetching/storing of NFO files.
 */
class Nfo
{
	public function __construct($echooutput=false)
	{
		$s = new Sites();
		$this->site = $s->get();
		$this->nzbs = (!empty($this->site->maxnfoprocessed)) ? $this->site->maxnfoprocessed : 100;
		$this->maxsize = (!empty($this->site->maxsizetopostprocess)) ? $this->site->maxsizetopostprocess : 100;
		$this->echooutput = $echooutput;
		$this->tmpPath = $this->site->tmpunrarpath;
		if (substr($this->tmpPath, -strlen( '/' ) ) != '/')
			$this->tmpPath = $this->tmpPath.'/';
		$this->c = new ColorCLI;
		$this->primary = 'Green';
		$this->warning = 'Red';
		$this->header = 'Yellow';
        $this->db = new DB();
	}

	public function addReleaseNfo($relid)
	{
		$db = $this->db;
		$res = $db->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $relid));
		if ($res == false)
			return $db->queryInsert(sprintf('INSERT INTO releasenfo (releaseid) VALUES (%d)', $relid));
		else
			return $res['id'];
	}

	public function deleteReleaseNfo($relid)
	{
		$db = $this->db;
		return $db->queryExec(sprintf('DELETE FROM releasenfo WHERE releaseid = %d', $relid));
	}

	// Find an IMDB ID in a NFO file.
	public function parseImdb($str)
	{
		preg_match('/(?:imdb.*?)?(?:tt|Title\?)(\d{5,7})/i', $str, $matches);
		if (isset($matches[1]) && !empty($matches[1]))
			return trim($matches[1]);

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
	public function isNFO($possibleNFO, $guid)
	{
		$r = false;
		if ($possibleNFO === false)
			return $r;
		// Make sure it's not too big or small, size needs to be at least 12 bytes for header checking.
		$size = strlen($possibleNFO);
		if ($size < 100 * 1024 && $size > 12)
		{
			// Ignore common file types.
			if (preg_match('/<\?xml|;\s*Generated\s*by.*SF\w|\A\s*PAR|\.[a-z0-9]{2,7}\s*[a-z0-9]{8}|\A\s*RAR|\A.{0,10}(JFIF|matroska|ftyp|ID3)|\A=newz\[NZB\]=/i', $possibleNFO))
				return $r;

			// Use getid3 to check if it's an image/video/rar/zip etc..
			require_once nZEDb_LIB . 'getid3/getid3/getid3.php';
			$getid3 = new getid3;
			// getid3 works with files, so save to disk
			$tmpPath = $this->tmpPath.$guid.'.nfo';
			file_put_contents($tmpPath, $possibleNFO);
			$check = $getid3->analyze($tmpPath);
			unset($getid3);
			@unlink($tmpPath);
			unset($tmpPath);
			if (isset($check['error']))
			{
				// Check if it's a par2.
				require_once nZEDb_LIB . 'rarinfo/par2info.php';
				$par2info = new Par2Info();
				$par2info->setData($possibleNFO);
				if ($par2info->error)
				{
					// Check if it's an SFV.
					require_once nZEDb_LIB . 'rarinfo/sfvinfo.php';
					$sfv = new SfvInfo;
					$sfv->setData($possibleNFO);
					if ($sfv->error)
						return true;
				}
			}
		}
		return $r;
	}

	// Adds an NFO found from predb, rar, zip etc...
	public function addAlternateNfo($db, $nfo, $release, $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Unable to connect to usenet.\n"));

		if ($release['id'] > 0)
		{
			if ($db->dbSystem() == 'mysql')
			{
				$compress = 'compress(%s)';
				$nc = $db->escapeString($nfo);
			}
			else
			{
				$compress = '%s';
				$nc = $db->escapeString(utf8_encode($nfo));
			}
			$ckreleaseid = $db->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $release['id']));
			if (!isset($ckreleaseid['id']))
				$db->queryInsert(sprintf('INSERT INTO releasenfo (nfo, releaseid) VALUES ('.$compress.', %d)', $nc, $release['id']));
			$db->queryExec(sprintf('UPDATE releases SET nfostatus = 1 WHERE id = %d', $release['id']));
			if (!isset($release['completion']))
				$release['completion'] = 0;
			if ($release['completion'] == 0)
			{
				$nzbcontents = new NZBcontents($this->echooutput);
				$nzbcontents->NZBcompletion($release['guid'], $release['id'], $release['groupid'], $nntp, $db);
			}
			return true;
		}
		else
			return false;
	}

	// Loop through releases, look for NFO's in the NZB file.
	public function processNfoFiles($releaseToWork = '', $processImdb=1, $processTvrage=1, $groupID='', $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Unable to connect to usenet.\n"));

		$db = $this->db;
		$nfocount = $ret = 0;
		$groupid = $groupID == '' ? '' : 'AND groupid = '.$groupID;

		if ($releaseToWork == '')
		{
			$i = -1;
			while (($nfocount != $this->nzbs) && ($i >= -6))
			{
				$res = $db->query(sprintf('SELECT id, guid, groupid, name FROM releases WHERE nzbstatus = 1 AND nfostatus between %d AND -1 AND size < %s '.$groupid.' LIMIT %d', $i, $this->maxsize*1073741824, $this->nzbs));
				$nfocount = count($res);
				$i--;
			}
		}
		else
		{
			$res = 0;
			$pieces = explode('           =+=            ', $releaseToWork);
			$res = array(array('id' => $pieces[0], 'guid' => $pieces[1], 'groupid' => $pieces[2], 'name' => $pieces[3]));
			$nfocount = 1;
		}

		if ($nfocount > 0)
		{
			if ($this->echooutput && $releaseToWork == '')
				echo $this->c->set256($this->primary).'Processing '.$nfocount.' NFO(s), starting at '.$this->nzbs." * = hidden NFO, + = NFO, - = no NFO, f = download failed.\n".$this->c->rsetcolor();
			$groups = new Groups();
			$nzbcontents = new NZBcontents($this->echooutput);
			$movie = new Movie($this->echooutput);
			$tvrage = new Tvrage();

			foreach ($res as $arr)
			{
				$fetchedBinary = $nzbcontents->getNFOfromNZB($arr['guid'], $arr['id'], $arr['groupid'], $nntp, $groups->getByNameByID($arr['groupid']), $db, $this);
				if ($fetchedBinary !== false)
				{
					// Insert nfo into database.
					if ($db->dbSystem() == 'mysql')
					{
						$cp = 'COMPRESS(%s)';
						$nc = $db->escapeString($fetchedBinary);
					}
					else if ($db->dbSystem() == 'pgsql')
					{
						$cp = '%s';
						$nc = $db->escapeString(utf8_encode($fetchedBinary));
					}
					$ckreleaseid = $db->queryOneRow(sprintf('SELECT id FROM releasenfo WHERE releaseid = %d', $arr['id']));
					if (!isset($ckreleaseid['id']))
						$db->queryInsert(sprintf('INSERT INTO releasenfo (nfo, releaseid) VALUES ('.$cp.', %d)', $nc, $arr['id']));
					$db->queryExec(sprintf('UPDATE releases SET nfostatus = 1 WHERE id = %d', $arr['id']));
					$ret++;
					$movie->domovieupdate($fetchedBinary, 'nfo', $arr['id'], $processImdb);

					// If set scan for tvrage info.
					if ($processTvrage == 1)
					{
						$rageId = $this->parseRageId($fetchedBinary);
						if ($rageId !== false)
						{
							$show = $tvrage->parseNameEpSeason($arr['name']);
							if (is_array($show) && $show['name'] != '')
							{
								// Update release with season, ep, and airdate info (if available) from releasetitle.
								$tvrage->updateEpInfo($show, $arr['id']);

								$rid = $tvrage->getByRageID($rageId);
								if (!$rid)
								{
									$tvrShow = $tvrage->getRageInfoFromService($rageId);
									$tvrage->updateRageInfo($rageId, $show, $tvrShow, $arr['id']);
								}
							}
						}
					}
				}
			}
		}

		// Remove nfo that we cant fetch after 5 attempts.
		if ($releaseToWork == '')
		{
			$relres = $db->query('SELECT id FROM releases WHERE nzbstatus = 1 AND nfostatus < -6');
			foreach ($relres as $relrow)
				$db->queryExec(sprintf('DELETE FROM releasenfo WHERE nfo IS NULL and releaseid = %d', $relrow['id']));

			if ($this->echooutput)
			{
				if ($this->echooutput && $nfocount > 0 && $releaseToWork == '')
					echo "\n";
				if ($this->echooutput && $ret > 0 && $releaseToWork == '')
					echo $ret." NFO file(s) found/processed.\n";
			}
			return $ret;
		}
	}
}
