<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");
require_once(WWW_DIR."/lib/nfo.php");

/*
 * Returns information contained within the NZB. Used for finding NFO's for now.
*/
Class NZBcontents
{
	function NZBcontents($echooutput=false)
	{
		$this->echooutput = $echooutput;
	}
	
	public function getNfoFromNZB($guid, $relID, $groupID, $nntp)
	{
		if($fetchedBinary = $this->NFOfromNZB($guid, $relID, $groupID, $nntp))
		{
			return $fetchedBinary;
		}
		else if ($fetchedBinary = $this->hiddenNFOfromNZB($guid, $relID, $groupID, $nntp))
		{
			return $fetchedBinary;
		}
		else
		{
			return false;
		}
	}
	
	//
	// Look for an .nfo file in the nzb, return the nfo.
	//
	public function NFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		$db = new DB();
		$nfo = new NFO();
		$nzb = new NZB();
		$groups = new Groups();
		// Fetch the NZB location using the GUID.
		if ($nzbpath = $nzb->NZBPath($guid))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			// Fetch the NZB.
			$nzbfile = simplexml_load_file($nzbpath);
			$foundnfo = false;
			
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/\.nfo/', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					$foundnfo = true;
					break;
				}
			}
			if ($foundnfo !== false)
			{
				if ($messageid !== false)
				{
					$nfo->addReleaseNfo($relID);
					$groupName = $groups->getByNameByID($groupID);
					$fetchedBinary = $nntp->getMessage($groupName, $messageid);
					if ($this->echooutput)
						echo "+";
					return $fetchedBinary;
				}
			}
		}
		else
		{
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
			return false;
		}
	}
	
	//
	// Look for an NFO in the nzb which does not end in .nfo, return the nfo.
	//
	public function hiddenNFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		$notout = '/\.(apk|bat|bmp|cbr|cbz|cfg|css|csv|cue|db|dll|doc|exe|gif|htm|ico|idx|ini|jpg|log|m3u|mid|nzb|odt|par2|pdf|psd|pps|png|ppt|sfv|sub|srt|sql|rom|rtf|tif|torrent|ttf|txt|vb|wps|xml)/i';
		$notin = '/<?xml|;\sGenerated\sby.+SF\w|^PAR2|\.[a-z0-9]{2,7}\s[a-z0-9]{8}/i';
		$db = new DB();
		$nfo = new NFO();
		$nzb = new NZB();
		$groups = new Groups();
		// Fetch the NZB location using the GUID.
		if ($nzbpath = $nzb->NZBPath($guid))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			// Fetch the NZB.
			$nzbfile = simplexml_load_file($nzbpath);
			$foundnfo = false;
			$failed = false;
			$groupName = $groups->getByNameByID($groupID);
			
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/yEnc\s\(1\/1\)/', $subject) && !preg_match($notout, $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					if ($messageid !== false)
					{
						$possibleNFO = $nntp->getMessage($groupName, $messageid);
						if ($possibleNFO !== false)
						{
							if (!preg_match($notin, $possibleNFO))
							{
								$pNFOsize = strlen($possibleNFO);
								if ($pNFOsize < 40000)
								{
									// exif_imagetype needs a minimum size or else it doesn't work
									if ($pNFOsize > 20)
									{
										//Check if it's an image.
										if (@exif_imagetype($possibleNFO) == false)
										{
											$fetchedBinary = $possibleNFO;
											$foundnfo = true;
										}
									}
									else
									{
										$fetchedBinary = $possibleNFO;
										$foundnfo = true;
									}
								}
							}
							else
							{
								//nfo download failed, increment attempts
								$db->queryDirect(sprintf("UPDATE releases SET nfostatus = nfostatus-1 WHERE ID = %d", $arr["ID"]));
								$failed = true;
							}
						}
					}
				}
			}
			if ($foundnfo !== false && $failed == false)
			{
				$nfo->addReleaseNfo($relID);
				if ($this->echooutput)
					echo "*";
				return $fetchedBinary;
			}
			if ($foundnfo == false && $failed == false)
			{
				//No .nfo file in the NZB.
				if ($this->echooutput)
					echo "-";
				$db->queryDirect(sprintf("update releases set nfostatus = 0 where nfostatus not between -6 and -1 and ID = %d", $relID));
				return false;
			}
			if ($failed == true)
			{
				return false;
			}
		}
		else
		{
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
			return false;
		}
	}
}
