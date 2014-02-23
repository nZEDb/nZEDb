<?php
/* Fixes NZB files with a blank first line. */

require dirname(__FILE__) . '/../../../www/config.php';
$c = new ColorCLI();

if (isset($argv[1]) && $argv[1] == "true")
{
	$timestart = TIME();
	$nzbcount = $brokencount = 0;
	$db = new DB();

	$guids = $db->queryDirect("SELECT guid FROM releases WHERE nzbstatus = 1 ORDER BY postdate DESC");
	echo $c->primary("Be patient, this WILL take a very long time, make sure to kill all nZEDb scripts first. There are " . number_format($guids->rowCount()) . " NZB files to scan.");

	foreach ($guids as $guid)
	{
		$nzb = new NZB();
		if(file_exists($nzbpath = $nzb->NZBPath($guid["guid"]))) {
			$nzbcount++;
			$nzbpathc = 'compress.zlib://'.$nzbpath;
			$nzbfile = file_get_contents($nzbpathc);

			if (preg_match("/^[\r\n]+<\?xml/", $nzbfile)) {
				$brokencount++;
				$nzbfile = preg_replace('/^[\r\n]+<\?xml/i', '<?xml', $nzbfile);
				$nzb = preg_replace('/<\/nzb>.+/i', '</nzb>', $nzbfile);

				unlink($nzbpath);
				$fp = gzopen($nzbpath, 'w6');

				if ($fp) {
					gzwrite($fp, $nzb, strlen($nzb));
					gzclose($fp);
					chmod($nzbpath, 0777);
				}
			}
			if ($nzbcount % 5000 == 0) {
				echo $nzbcount." NZBs scanned. ".$brokencount." NZBs fixed. ".(TIME() - $timestart)." seconds.\n";
			} else if ($nzbcount % 1000 == 0) {
				echo "\n";
			} else if ($nzbcount % 10 == 0) {
				echo ".";
			}
		} else {
			echo $c->error("\nWrong permissions on NZB file, or it does not exist.\n");
		}
		unset($guid);
	}
	echo $c->header($nzbcount." NZB files scanned. in " . TIME() - $timestart . " seconds. ".$brokencount." NZB files were fixed.");
} else {
	exit($c->error("\nThis script can be dangerous, if you are sure you want to run this, STOP ALL OTHER nZEDb SCRIPTS.\n\n"
				. "php $argv[0] true     ...: To remove blank lines from all nzbs.\n"));
}
