<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");

//
// Returns information on the NZB for post-processing.
//


/*
 * Post processing looks at the subject(binaries) for a rar, sample and mediafile.
 * If it finds one it retrieves 1 message-ID, downloads it and tries to post process.
 * So we must do the same here, but using the NZB.
 * 
 * 1. Fetch the NZB.
 * 2. Look for sample, ignore if the extension is srs or par2.
 * 3. Look for a mediafile, ignore if the extension is srs or par2.
 * 4. Look for a rar, ignore if extension is sub. [-_\.]sub
 * 
 */
 
Class NZBcontents
{
	function NZBcontents()
	{
		$this->mediafileregex = 'AVI|VOB|MKV|MP4|TS|WMV|MOV|M4V|F4V|MPG|MPEG';
	}
	
	//
	// Retrieve the location of an nzb.
	//
	public function getNzbContents($guid)
	{
		$n = "\n";
		$nzb = new NZB();
		$nzbpath = $nzb->getNZBPath($guid);
		echo $nzbpath.$n;
		
		$nzbpath = 'compress.zlib://'.$nzbpath;
		
		$nzbfile = simplexml_load_file($nzbpath);
		
		echo $nzbfile->head->meta.$n;
		
	}
}
