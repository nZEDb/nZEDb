<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");

/*
 * Returns information contained within the NFO.
*/
Class NZBcontents
{

	//
	// Look for an NFO in the nzb, return the message-ID.
	//
	public function getNFOfromNZB($guid)
	{
		// Fetch the NZB location using the GUID.
		$nzb = new NZB();
		$nzbpath = $nzb->getNZBPath($guid);
		$nzbpath = 'compress.zlib://'.$nzbpath;
		// Fetch the NZB.
		$nzbfile = simplexml_load_file($nzbpath);
		
		foreach ($nzbfile->file as $nzbcontents)
		{
			$subject = $nzbcontents->attributes()->subject;
			if (preg_match('/\.nfo/', $subject))
			{
				$segments = $nzbcontents->segments->segment;
				return $segments;
			}
		}
	}
	
	//
	// Look for an NFO in the nzb, return the message-ID.
	// Look inside files (1/1) to see if they are a nfo.
	//
	// Not functional yet.
	//
	public function getNFOfromNZB2($guid, $releaseID)
	{
		// Fetch the NZB location using the GUID.
		$nzb = new NZB();
		$nzbpath = $nzb->getNZBPath($guid);
		$nzbpath = 'compress.zlib://'.$nzbpath;
		// Fetch the NZB.
		$nzbfile = simplexml_load_file($nzbpath);
		
		foreach ($nzbfile->file as $nzbcontents)
		if (preg_match('/\(1\/1\)\syEnc/', $subject))
		{
			$segments = $nzbcontents->segments->segment;
			$nntp = new Nntp();
			$groups = new Groups;
			$groupName = $groups->getByNameByID($groupID);
		}
	}
}

