<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");

/*
 * Returns information contained within the NFO.
*/
Class NZBcontents
{

	//
	// Look for an .nfo file in the nzb, return the message-ID.
	//
	public function getNFOfromNZB($guid, $groupID)
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
				if (preg_match('/\.nfo/', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					return $messageid;
				}
				/* Look for a nfo that does not end with .nfo
				else if ($messageid = $this->getHiddenNFOfromNZB($guid, $groupID))
				{
					return $messageid;
				}*/
				else
				{
					//no nfo found
					echo "false\n";
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
				if (preg_match('/\.nfo/', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					return $messageid;
				}
				/* Look for a nfo that does not end with .nfo
				else if (preg_match('/\(1\/1\)\syEnc/', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
				}*/
			}
		}
		else
		{
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
			return false;
		}
	}
}

