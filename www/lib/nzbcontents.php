<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");
require_once(WWW_DIR."/lib/nfo.php");

/*
 * Returns information contained within the NFO.
*/
Class NZBcontents
{
	//
	// Look for an .nfo file in the nzb, return the message-ID.
	//
	public function getNFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		// Fetch the NZB location using the GUID.
		$db = new DB();
		$nfo = new NFO();
		$nzb = new NZB();
		$groups = new Groups();
		if ($nzbpath = $nzb->NZBPath($guid))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			// Fetch the NZB.
			$nzbfile = simplexml_load_file($nzbpath);
		
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/\.nfo/', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					if ($messageid !== false)
					{
						$nfo->addReleaseNfo($relID);
						$groupName = $groups->getByNameByID($groupID);
						$fetchedBinary = $nntp->getMessage($groupName, $messageid);
						echo ".+";
						return $fetchedBinary;
					}
					else
					{
						//Error fetching the message-ID from the nzb, increment attempts
						$db->queryDirect(sprintf("UPDATE releases SET nfostatus = nfostatus-1 WHERE ID = %d", $relID));
						return false;
					}
				}
				/* Look for a nfo that does not end with .nfo
				else if ($messageid = $this->getHiddenNFOfromNZB($guid, $groupID))
				{
					return $messageid;
				}*/
				else
				{
					//No .nfo file in the NZB.
					echo ".-";
					$db->queryDirect(sprintf("update releases set nfostatus = 0 where ID = %d", $relID));
					return false;
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
	// Look for an NFO in the nzb which does not end in .nfo, return the message-ID.
	//
	public function getHiddenNFOfromNZB($guid, $groupID)
	{
		// Fetch the NZB location using the GUID.
		$nzb = new NZB();
		if ($nzbpath = $nzb->NZBPath($guid))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			// Fetch the NZB.
			$nzbfile = simplexml_load_file($nzbpath);
		
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				//Look for a nfo that does not end with .nfo
				if (preg_match('/\(1\/1\)\syEnc/', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
				}
			}
		}
		else
		{
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
			return false;
		}
	}
}

