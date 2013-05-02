<?php

/*
 * Fixes NZB files with a blank first line.
 */
 
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/nzb.php");
require_once(WWW_DIR."/lib/framework/db.php");

if (isset($argv[1]) && $argv[1] == "true")
{
	$timestart = TIME();
	$nzbcount = 0;
	$db = new DB();

	$guids = $db->query("select guid from releases where nzbstatus = 1");
	echo "Be patient, this WILL take a very long time, make sure to kill all nZEDb scripts first. There are ".sizeof($guids)." NZB files to scan.\n";

	foreach ($guids as $guid)
	{
		$nzb = new NZB();
		if(file_exists($nzbpath = $nzb->NZBPath($guid["guid"])))
		{
			$nzbcount++;
			$nzbpathc = 'compress.zlib://'.$nzbpath;
			$nzbfile = file_get_contents($nzbpathc);
			
			if (preg_match("/^[\r\n]+<\?xml/", $nzbfile))
			{
				$nzbfile = preg_replace('/^[\r\n]+<\?xml/i', '<?xml', $nzbfile);
				$nzb = preg_replace('/<\/nzb>.+/i', '</nzb>', $nzbfile);
				
				unlink($nzbpath);
				$fp = gzopen($nzbpath, 'w5'); 
			
				if ($fp)
				{
					gzwrite($fp, $nzb, strlen($nzb));
					gzclose($fp); 
					chmod($nzbpath, 0777);
				}
			}
			if ($nzbcount % 10 == 0)
			{
				echo ".";
			}
			if ($nzbcount % 1000 == 0)
			{
				echo "\n";
			}
			if ($nzbcount % 5000 == 0)
			{
				echo $nzbcount." NZBs scanned.\n";
			}
		}
		else
		{
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
		}
	}
	echo "\n".$nzbcount." NZB files scanned. in ";
	echo TIME() - $timestart;
	echo " seconds.\n";
}
else
{
	exit("This script can be dangerous, if you are sure you want to run this, STOP ALL OTHER NZEDB SCRIPTS, type php fix_blank_line_nzb.php true ; be patient, this can take a long time.\n");
}

?>
