<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use app\models\Settings;
use nzedb\ConsoleTools;
use nzedb\NZB;
use nzedb\ReleaseImage;
use nzedb\Releases;
use nzedb\db\DB;
use nzedb\utility\Misc;

$pdo = new DB();

$dir = nZEDb_RES . "movednzbs/";

if (!isset($argv[1]) || !in_array($argv[1], ["true", "move"])) {
	exit($pdo->log->error("\nThis script can remove all nzbs not found in the db and all releases with no nzbs found. It can also move invalid nzbs.\n\n"
		. "php $argv[0] true     ...: For a dry run, to see how many would be moved.\n"
		. "php $argv[0] move     ...: Move NZBs that are possibly bad or have no release. They are moved into this folder: $dir\n"));
}

if (!is_dir($dir) && !mkdir($dir)) {
	exit("ERROR: Could not create folder [$dir]." . PHP_EOL);
}

$releases = new Releases(['Settings' => $pdo]);
$nzb = new NZB($pdo);
$releaseImage = new ReleaseImage($pdo);

$timestart = date("r");
$checked = $moved = 0;
$couldbe = ($argv[1] === "true") ? "could be " : "";

echo $pdo->log->header('Getting List of nzbs to check against db.');
echo $pdo->log->header("Checked / {$couldbe}moved\n");

$dirItr = new \RecursiveDirectoryIterator(Settings::value('..nzbpath'));
$itr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::LEAVES_ONLY);

foreach ($itr as $filePath) {
	$guid = stristr($filePath->getFilename(), '.nzb.gz', true);
	if (is_file($filePath) && $guid) {
		$nzbfile = Misc::unzipGzipFile($filePath);
		$nzbContents = $nzb->nzbFileList($nzbfile, ['no-file-key' => false, 'strip-count' => true]);
		if (!$nzbfile || !@simplexml_load_string($nzbfile) || count($nzbContents) === 0) {
			if ($argv[1] === "move") {
				rename($filePath, $dir . $guid . ".nzb.gz");
			}
			$releases->deleteSingle(['g' => $guid, 'i' => false], $nzb, $releaseImage);
			$moved++;
		}
		++$checked;
		echo "$checked / $moved\r";
	}
}

echo $pdo->log->header("\n" . number_format($checked) . ' nzbs checked, ' . number_format($moved) . ' nzbs ' . $couldbe . 'moved.');
echo $pdo->log->header("Getting List of releases to check against nzbs.");
echo $pdo->log->header("Checked / releases deleted\n");

$checked = $deleted = 0;

$res = $pdo->queryDirect('SELECT id, guid, nzbstatus FROM releases');
if ($res instanceof \Traversable) {
	foreach ($res as $row) {
		$nzbpath = $nzb->getNZBPath($row["guid"]);
		if (!is_file($nzbpath)) {
			++$deleted;
			$releases->deleteSingle(['g' => $row['guid'], 'i' => $row['id']], $nzb, $releaseImage);
		} elseif ($row["nzbstatus"] != 1) {
			$pdo->queryExec(sprintf("UPDATE releases SET nzbstatus = 1 WHERE id = %d", $row['id']));
		}
		++$checked;
		echo "$checked / $deleted\r";
	}
}
echo $pdo->log->header("\n" . number_format($checked) . " releases checked, " . number_format($deleted) . " releases deleted.");
echo $pdo->log->header("Script started at [$timestart], finished at [" . date("r") . "]");
