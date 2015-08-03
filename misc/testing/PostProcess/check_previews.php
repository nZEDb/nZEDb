<?php
// --------------------------------------------------------------
//          Scan for releases missing previews on disk
// --------------------------------------------------------------
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ConsoleTools;
use nzedb\NZB;
use nzedb\ReleaseImage;
use nzedb\Releases;
use nzedb\db\Settings;
use nzedb\utility\Misc;

$pdo = new Settings();

$row = $pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'coverspath'");
if ($row !== false) {
	Misc::setCoversConstant($row['value']);
} else {
	die("Unable to determine covers path!\n");
}

$path2preview = nZEDb_COVERS . 'preview' . DS;

if (isset($argv[1]) && ($argv[1] === "true" || $argv[1] === "check")) {
	$releases = new Releases(['Settings' => $pdo]);
	$nzb = new NZB($pdo);
	$releaseImage = new ReleaseImage($pdo);
	$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);
	$couldbe = $argv[1] === "true" ? $couldbe = "were " : "could be ";
	$limit = $counterfixed = 0;
	if (isset($argv[2]) && is_numeric($argv[2])) {
		$limit = $argv[2];
	}
	echo $pdo->log->header("Scanning for releases missing previews");
	$res = $pdo->queryDirect("SELECT id, guid FROM releases where nzbstatus = 1 AND haspreview = 1");
	if ($res instanceof \Traversable) {
		foreach ($res as $row) {
			$nzbpath = $path2preview . $row["guid"] . "_thumb.jpg";
			if (!file_exists($nzbpath)) {
				$counterfixed++;
				echo $pdo->log->warning("Missing preview " . $nzbpath);
				if ($argv[1] === "true") {
					$pdo->queryExec(
						sprintf("UPDATE releases SET consoleinfoid = NULL, gamesinfo_id = 0, imdbid = NULL, musicinfoid = NULL,	bookinfoid = NULL, rageid = -1, xxxinfo_id = 0, passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0, nfostatus = -1 WHERE id = %s", $row['id']));
				}
			}

			if (($limit > 0) && ($counterfixed >= $limit)) {
				break;
			} // QUAD!
		}
	}
	echo $pdo->log->header("Total releases missing previews that " . $couldbe . "reset for reprocessing= " . number_format($counterfixed));
} else {
	exit($pdo->log->header("\nThis script checks if release previews actually exist on disk.\n\n"
			. "Releases without previews may be reset for post-processing, thus regenerating them and related meta data.\n\n"
			. "Useful for recovery after filesystem corruption, or as an alternative re-postprocessing tool.\n\n"
			. "Optional LIMIT parameter restricts number of releases to be reset.\n\n"
			. "php $argv[0] check [LIMIT]  ...: Dry run, displays missing previews.\n"
			. "php $argv[0] true  [LIMIT]  ...: Re-process releases missing previews.\n"));
}
