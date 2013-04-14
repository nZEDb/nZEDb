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
		$guids = $db->queryDirect("SELECT rn.*, guid, r.searchname FROM releasenfo rn left outer join releases r ON r.ID = rn.releaseID WHERE rn.nfo IS NULL"); //add this to the end later : AND rn.attempts < 5
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
