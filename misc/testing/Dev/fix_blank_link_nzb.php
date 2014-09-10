<?php
/* Fixes NZB files with a blank first line. */
require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (isset($argv[1]) && $argv[1] == "true") {
	$timestart = time();
	$nzbcount = $brokencount = 0;

	$guids = $pdo->queryDirect("SELECT guid FROM releases WHERE nzbstatus = 1 ORDER BY postdate DESC");
	echo $pdo->log->primary("Be patient, this WILL take a very long time, make sure to kill all nZEDb scripts first. There are " . number_format($guids->rowCount()) . " NZB files to scan.");
	$nzb = new \NZB($pdo);
	if ($guids instanceof \Traversable) {
		foreach ($guids as $guid) {
			$nzbpath = $nzb->NZBPath($guid["guid"]);
			if($nzbpath !== false) {
				$nzbcount++;
				$nzbfile = nzedb\utility\Utility::unzipGzipFile($nzbpath);

				if ($nzbfile && preg_match('/^[\r\n]+<\?xml/', $nzbfile)) {
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
				echo $pdo->log->error("\nWrong permissions on NZB file, or it does not exist.\n");
			}
			unset($guid);
		}
	}
	echo $pdo->log->header($nzbcount." NZB files scanned. in " . TIME() - $timestart . " seconds. ".$brokencount." NZB files were fixed.");
} else {
	exit($pdo->log->error("\nThis script can be dangerous, if you are sure you want to run this, STOP ALL OTHER nZEDb SCRIPTS.\n\n"
				. "php $argv[0] true     ...: To remove blank lines from all nzbs.\n"));
}
