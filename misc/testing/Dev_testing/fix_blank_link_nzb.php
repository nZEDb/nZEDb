<?php
/* Fixes NZB files with a blank first line. */

if (isset($argv[1]) && $argv[1] == "true")
{
	require(dirname(__FILE__)."/../../../www/config.php");
	require_once(WWW_DIR."/lib/nzb.php");
	require_once(WWW_DIR."/lib/framework/db.php");
	$timestart = TIME();
	$nzbcount = $brokencount = 0;
	$db = new DB();

	$guids = $db->query("SELECT guid FROM releases WHERE nzbstatus = 1 ORDER BY postdate DESC");
	echo "Be patient, this WILL take a very long time, make sure to kill all nZEDb scripts first. There are ".count($guids)." NZB files to scan.\n";

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
				$brokencount++;
				$nzbfile = preg_replace('/^[\r\n]+<\?xml/i', '<?xml', $nzbfile);
				$nzb = preg_replace('/<\/nzb>.+/i', '</nzb>', $nzbfile);

				unlink($nzbpath);
				$fp = gzopen($nzbpath, 'w6');

				if ($fp)
				{
					gzwrite($fp, $nzb, strlen($nzb));
					gzclose($fp);
					chmod($nzbpath, 0777);
				}
			}
			if ($nzbcount % 10 == 0)
				echo ".";
			if ($nzbcount % 1000 == 0)
				echo "\n";
			if ($nzbcount % 5000 == 0)
				echo $nzbcount." NZBs scanned. ".$brokencount." NZBs fixed. ".(TIME() - $timestart)." seconds.\n";
		}
		else
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
		unset($guid);
	}
	echo "\n".$nzbcount." NZB files scanned. in ";
	echo TIME() - $timestart;
	echo " seconds. ".$brokencount." NZB files were fixed.\n";
}
else
	exit("This script can be dangerous, if you are sure you want to run this, STOP ALL OTHER NZEDB SCRIPTS, type php fix_blank_line_nzb.php true ; be patient, this can take a long time.\n");

?>
