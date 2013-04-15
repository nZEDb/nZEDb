<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");
require_once(WWW_DIR."/lib/nfo.php");

/*
 * Returns information contained within the NFO.
*/
Class NZBcontents
{
	public function getNfoFromNZB($guid, $relID, $groupID, $nntp)
	{
		if($fetchedBinary = $this->NFOfromNZB($guid, $relID, $groupID, $nntp))
		{
			return $fetchedBinary;
		}
		//else if ($fetchedBinary = $this->hiddenNFOfromNZB($guid, $relID, $groupID, $nntp))
		//{
		//	return $fetchedBinary;
		//}
		else
		{
			return false;
		}
	}
	
	//
	// Look for an .nfo file in the nzb, return the message-ID.
	//
	public function NFOfromNZB($guid, $relID, $groupID, $nntp)
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
					echo ".+";
					return $fetchedBinary;
				}	
				else
				{
					//Error fetching the message-ID from the nzb, increment attempts
					echo "Error fetching the message-ID";
					$db->queryDirect(sprintf("UPDATE releases SET nfostatus = nfostatus-1 WHERE ID = %d", $relID));
					return false;
				}
			}
			else
			{
				//No .nfo file in the NZB.
				echo ".-";
				$db->queryDirect(sprintf("update releases set nfostatus = 0 where ID = %d", $relID));
				return false;
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
	public function hiddenNFOfromNZB($guid, $relID, $groupID, $nntp)
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
			$foundnfo = false;
			
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/yEnc\s\(1\/1\)/', $subject) && !preg_match('/\.(doc|idx|jpg|mp3|nfo|txt|par2|sub|sfv)/', $subject))
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
					echo ".+";
					return $fetchedBinary;
				}	
				else
				{
					//Error fetching the message-ID from the nzb, increment attempts
					echo "Error fetching the message-ID";
					$db->queryDirect(sprintf("UPDATE releases SET nfostatus = nfostatus-1 WHERE ID = %d", $relID));
					return false;
				}
			}
			else
			{
				//No .nfo file in the NZB.
				echo ".-";
				$db->queryDirect(sprintf("update releases set nfostatus = 0 where ID = %d", $relID));
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

