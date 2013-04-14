<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");

/*
 * Returns information contained within the NFO.
*/
Class NZBcontents
{
	function NZBcontents()
	{
		
	}
	//
	// Look for an NFO in the nzb, return the message-ID.
	//
	public function getNFOfromNZB()
	{
		$db = new DB();
		$guids = $db->queryDirect("select ID, guid from releases where releases.ID not in (select releaseID from releasenfo) order by adddate asc limit 0,50"); //add this to the end later : AND nfostatus between -1 and -6
		while ($relguid = mysql_fetch_assoc($guids))
		{
			$guid = $relguid["guid"];
			$n = "\n";
			// Fetch the NZB location using the GUID.
			$nzb = new NZB();
			$nzbpath = $nzb->getNZBPath($guid);
			$nzbpath = 'compress.zlib://'.$nzbpath;
			// Fetch the NZB.
			$nzbfile = simplexml_load_file($nzbpath);
			//print_r($nzbfile);
		
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				//print_r($subject);
				if (preg_match('/\.nfo/', $subject))
				{
					print_r($subject);
					$segments = $nzbcontents->segments->segment;
					//print_r((string)$segments.$n);
				}
			}
		}
	}
}
