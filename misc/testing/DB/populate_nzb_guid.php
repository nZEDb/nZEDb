<?php
// This script updates all releases with the guid from the nzb file.

require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$cli = new \ColorCLI();
if (isset($argv[1])) {
	$del = false;
	if (isset($argv[2])) {
		$del = $argv[2];
	}
	create_guids($argv[1], $del);
} else {
	exit($cli->error("\nThis script updates all releases with the guid (md5 hash of the first message-id) from the nzb file.\n\n"
			. "php $argv[0] true         ...: To create missing nzb_guids.\n"
			. "php $argv[0] true delete  ...: To create missing nzb_guids and delete invalid nzbs and releases.\n"));
}

function create_guids($live, $delete = false)
{
	$pdo = new Settings();
	$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
	$timestart = TIME();
	$relcount = $deleted = $total = 0;

	$relrecs = false;
	if ($live == "true") {
		$relrecs = $pdo->queryDirect(sprintf("SELECT id, guid FROM releases WHERE nzbstatus = 1 AND nzb_guid IS NULL ORDER BY id DESC"));
	} else if ($live == "limited") {
		$relrecs = $pdo->queryDirect(sprintf("SELECT id, guid FROM releases WHERE nzbstatus = 1 AND nzb_guid IS NULL ORDER BY id DESC LIMIT 10000"));
	}
	if ($relrecs) {
		$total = $relrecs->rowCount();
	}
	if ($total > 0) {
		echo $pdo->log->header("Creating nzb_guids for " . number_format($total) . " releases.");
		$releases = new \Releases(['Settings' => $pdo]);
		$nzb = new \NZB($pdo);
		$releaseImage = new \ReleaseImage($pdo);
		$reccnt = 0;
		if ($relrecs instanceof \Traversable) {
			foreach ($relrecs as $relrec) {
				$reccnt++;
				$nzbpath = $nzb->NZBPath($relrec['guid']);
				if ($nzbpath !== false) {
					$nzbfile = nzedb\utility\Utility::unzipGzipFile($nzbpath);
					if ($nzbfile) {
						$nzbfile = @simplexml_load_string($nzbfile);
					}
					if (!$nzbfile) {
						if (isset($delete) && $delete == 'delete') {
							//echo "\n".$nzb->NZBPath($relrec['guid'])." is not a valid xml, deleting release.\n";
							$releases->deleteSingle(['g' => $relrec['guid'], 'i' => $relrec['id']], $nzb, $releaseImage);
							$deleted++;
						}
						continue;
					}
					$binary_names = array();
					foreach ($nzbfile->file as $file) {
						$binary_names[] = $file["subject"];
					}
					if (count($binary_names) == 0) {
						if (isset($delete) && $delete == 'delete') {
							//echo "\n".$nzb->NZBPath($relrec['guid'])." has no binaries, deleting release.\n";
							$releases->deleteSingle(['g' => $relrec['guid'], 'i' => $relrec['id']], $nzb, $releaseImage);
							$deleted++;
						}
						continue;
					}

					asort($binary_names);
					foreach ($nzbfile->file as $file) {
						if ($file["subject"] == $binary_names[0]) {
							$segment = $file->segments->segment;
							$nzb_guid = md5($segment);

							$pdo->queryExec("UPDATE releases set nzb_guid = " . $pdo->escapestring($nzb_guid) . " WHERE id = " . $relrec["id"]);
							$relcount++;
							$consoletools->overWritePrimary("Created: [" . $deleted . "] " . $consoletools->percentString($reccnt, $total) . " Time:" . $consoletools->convertTimer(TIME() - $timestart));
							break;
						}
					}
				} else {
					if (isset($delete) && $delete == 'delete') {
						//echo $pdo->log->primary($nzb->NZBPath($relrec['guid']) . " does not have an nzb, deleting.");
						$releases->deleteSingle(['g' => $relrec['guid'], 'i' => $relrec['id']], $nzb, $releaseImage);
					}
				}
			}
		}

		if ($relcount > 0) {
			echo "\n";
		}
		echo $pdo->log->header("Updated " . $relcount . " release(s). This script ran for " . $consoletools->convertTime(TIME() - $timestart));
	} else {
		echo $pdo->log->info('Query time: ' . $consoletools->convertTime(TIME() - $timestart));
		exit($pdo->log->info("No releases are missing the guid."));
	}
}
