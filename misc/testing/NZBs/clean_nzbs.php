<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ConsoleTools;
use nzedb\NZB;
use nzedb\ReleaseImage;
use nzedb\Releases;
use nzedb\db\Settings;
use nzedb\utility\Misc;

if (!isset($argv[1]) || !in_array($argv[1], ["true", "delete"])) {
	exit($pdo->log->error("\nThis script can remove all nzbs not found in the db and all releases with no nzbs found. It can also delete invalid nzbs.\n\n"
		. "php $argv[0] true     ...: For a dry run, to see how many would be deleted.\n"
		. "php $argv[0] delete   ...: To delete all affected.\n"));
}

$pdo = new Settings();
$releases = new Releases(['Settings' => $pdo]);
$nzb = new NZB($pdo);
$releaseImage = new ReleaseImage($pdo);

$timestart = date("r");
$checked = $deleted = 0;
$couldbe = ($argv[1] === "true") ? "could be " : "";

echo $pdo->log->header('Getting List of nzbs to check against db.');
echo $pdo->log->header("Checked / {$couldbe}deleted\n");

$dirItr = new \RecursiveDirectoryIterator($pdo->getSetting('nzbpath'));
$itr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::LEAVES_ONLY);

foreach ($itr as $filePath) {
	if (is_file($filePath) && preg_match('/([a-f-0-9]+)\.nzb\.gz/', $filePath, $guid)) {
		$nzbfile = Misc::unzipGzipFile($filePath);
		if ($nzbfile && @simplexml_load_string($nzbfile)) {
			$res = $pdo->queryOneRow(sprintf("SELECT id, guid FROM releases WHERE guid = %s", $pdo->escapeString(stristr($filePath->getFilename(), '.nzb.gz', true))));
			if ($res === false) {
				$deleted++;
				if ($argv[1] === "delete") {
					@copy($filePath, nZEDb_ROOT . "pooped/" . $guid[1] . ".nzb.gz");
					$releases->deleteSingle(['g' => $guid[1], 'i' => false], $nzb, $releaseImage);
				}
			} else {
				$pdo->queryExec(sprintf("UPDATE releases SET nzbstatus = 1 WHERE id = %s", $res['id']));
			}
		} else {
			$deleted++;
			if ($argv[1] === "delete") {
				@copy($filePath, nZEDb_ROOT . "pooped/" . $guid[1] . ".nzb.gz");
			}
		}
		++$checked;
		echo "$checked / $deleted\r";
	}
}

echo $pdo->log->header("\n" . number_format($checked) . ' nzbs checked, ' . number_format($deleted) . ' nzbs ' . $couldbe . 'deleted.');
echo $pdo->log->header("Getting List of releases to check against nzbs.");
echo $pdo->log->header("Checked / {$couldbe}deleted\n");

$checked = $deleted = 0;

$res = $pdo->queryDirect('SELECT id, guid FROM releases');
if ($res instanceof \Traversable) {
	foreach ($res as $row) {
		$nzbpath = $nzb->getNZBPath($row["guid"]);
		if (is_file($nzbpath)) {
			$pdo->queryExec(sprintf("UPDATE releases SET nzbstatus = 1 WHERE id = %d", $row['id']));
		} else {
			$deleted++;
			if ($argv[1] === "delete") {
				@copy($nzbpath, nZEDb_ROOT . "pooped/" . $row["guid"] . ".nzb.gz");
				$releases->deleteSingle(['g' => $row['guid'], 'i' => $row['id']], $nzb, $releaseImage);
			}
		}
		++$checked;
		echo "$checked / $deleted\r";
	}
}
echo $pdo->log->header("\n" . number_format($checked) . " releases checked, " . number_format($deleted) . " releases " . $couldbe . "deleted.");
echo $pdo->log->header("Script started at [$timestart], finished at [" . date("r") . "]");
