<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (isset($argv[1]) && ($argv[1] === "true" || $argv[1] === "delete")) {
	$releases = new \Releases(['Settings' => $pdo]);
	$nzb = new \NZB($pdo);
	$releaseImage = new \ReleaseImage($pdo);
	$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
	$timestart = time();
	$checked = $deleted = 0;
	$couldbe = $argv[1] === "true" ? $couldbe = "could be " : "were ";
	echo $pdo->log->header('Getting List of nzbs to check against db.');
	$dirItr = new \RecursiveDirectoryIterator($pdo->getSetting('nzbpath'));
	$itr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::LEAVES_ONLY);
	foreach ($itr as $filePath) {
		if (is_file($filePath) && preg_match('/([a-f-0-9]+)\.nzb\.gz/', $filePath, $guid)) {
			$nzbfile = nzedb\utility\Utility::unzipGzipFile($filePath);
			if ($nzbfile) {
				$nzbfile = @simplexml_load_string($nzbfile);
			}
			if ($nzbfile) {
				$res = $pdo->queryOneRow(sprintf("SELECT id, guid FROM releases WHERE guid = %s", $pdo->escapeString(stristr($filePath->getFilename(), '.nzb.gz', true))));
				if ($res === false) {
					if ($argv[1] === "delete") {
						@copy($filePath, nZEDb_ROOT . "pooped/" . $guid[1] . ".nzb.gz");
						$releases->deleteSingle(['g' => $guid[1], 'i' => false], $nzb, $releaseImage);
						$deleted++;
					}
				} else if (isset($res)) {
					$pdo->queryExec(sprintf("UPDATE releases SET nzbstatus = 1 WHERE id = %s", $res['id']));
				}
			} else {
				if ($argv[1] === "delete") {
					@copy($filePath, nZEDb_ROOT . "pooped/" . $guid[1] . ".nzb.gz");
					unlink($filePath);
					$deleted++;
				}
			}
			$time = $consoletools->convertTime(time() - $timestart);
			$consoletools->overWritePrimary('Checking NZBs: ' . $deleted . ' nzbs of ' . ++$checked . ' releases checked ' . $couldbe . 'deleted from disk,  Running time: ' . $time);
		}
	}
	echo $pdo->log->header("\n" . number_format($checked) . ' nzbs checked, ' . number_format($deleted) . ' nzbs ' . $couldbe . 'deleted.');

	$timestart = time();
	$checked = $deleted = 0;
	echo $pdo->log->header("Getting List of releases to check against nzbs.");
	$res = $pdo->queryDirect('SELECT id, guid FROM releases');
	if ($res instanceof \Traversable) {
		foreach ($res as $row) {
			$nzbpath = $nzb->getNZBPath($row["guid"]);
			if (!file_exists($nzbpath)) {
				if ($argv[1] === "delete") {
					@copy($nzbpath, nZEDb_ROOT . "pooped/" . $row["guid"] . ".nzb.gz");
					$releases->deleteSingle(['g' => $row['guid'], 'i' => $row['id']], $nzb, $releaseImage);
				}
				$deleted++;
			} else if (file_exists($nzbpath) && isset($row)) {
				$pdo->queryExec(sprintf("UPDATE releases SET nzbstatus = 1 WHERE id = %s", $row['id']));
			}

			$time = $consoletools->convertTime(TIME() - $timestart);
			$consoletools->overWritePrimary('Checking Releases: ' . $deleted . " releases have no nzb of " . ++$checked . " and " . $couldbe . "deleted from db,  Running time: " . $time);
		}
	}
	echo $pdo->log->header("\n" . number_format($checked) . " releases checked, " . number_format($deleted) . " releases " . $couldbe . "deleted.");
} else {
	exit($pdo->log->error("\nThis script can remove all nzbs not found in the db and all releases with no nzbs found. It can also delete invalid nzbs.\n\n"
			. "php $argv[0] true     ...: For a dry run, to see how many would be deleted.\n"
			. "php $argv[0] delete   ...: To delete all affected.\n"));
}
