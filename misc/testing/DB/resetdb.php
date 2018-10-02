<?php
require_once realpath(dirname(__DIR__, 3) . '/app/config/bootstrap.php');

use app\models\Settings;
use nzedb\ConsoleTools;
use nzedb\NZB;
use nzedb\ReleaseImage;
use nzedb\SphinxSearch;
use nzedb\db\DB;

passthru('clear');
$pdo = new DB();

if (!isset($argv[1]) || (isset($argv[1]) && $argv[1] !== 'true')) {
	exit($pdo->log->error("\nThis script removes all releases and release related files. To run:\nphp resetdb.php true\n"));
}

echo $pdo->log->warning("This script removes all releases, nzb files, samples, previews , nfos, truncates all article tables and resets all groups.");
echo $pdo->log->header("Are you sure you want reset the DB?  Type 'DESTROY' to continue:  \n");
echo $pdo->log->warningOver("\n");
$line = fgets(STDIN);
if (trim($line) != 'DESTROY') {
	exit($pdo->log->error("This script is dangerous you must type DESTROY for it function."));
}

echo "\n";
echo $pdo->log->header("Thank you, continuing...\n\n");

$timestart = time();
$relcount = 0;
$ri = new ReleaseImage($pdo);
$nzb = new NZB($pdo);
$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);

$pdo->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, last_updated = NULL");
echo $pdo->log->primary("Reseting all groups completed.");

$arr = [
		"videos", "tv_episodes", "tv_info", "release_nfos", "release_comments", 'sharing', 'sharing_sites',
		"users_releases", "user_movies", "user_series", "movieinfo", "musicinfo", "release_files",
		"audio_data", "release_subtitles", "video_data", "releaseextrafull", "releases", "anidb_titles",
		"anidb_info", "anidb_episodes", "releases_groups"
];

// Truncate applicable tables
foreach ($arr as &$value) {
	$rel = $pdo->queryExec("TRUNCATE TABLE $value");
	if ($rel !== false) {
		echo $pdo->log->primary("Truncating ${value} completed.");
	}
}
unset($value);

$sql = "CALL loop_cbpm('truncate')";
echo $pdo->log->primary("Truncating binaries, collections, missed_parts and parts tables...");
$result = $pdo->query($sql);
echo $pdo->log->primary("Truncating completed.");

// Truncate Sphinx Index
(new SphinxSearch())->truncateRTIndex('releases_rt');

// Optimize DB after Reset
//$pdo->optimise(false, 'full');

// Delete NZBs
echo $pdo->log->header("Deleting nzbfiles subfolders.");
try {
	$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					Settings::value('..nzbpath'),
					\RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		if (basename($file) != '.gitignore' && basename($file) != 'tmpunrar') {
			$todo = ($file->isDir() ? 'rmdir' : 'unlink');
			@$todo($file);
		}
	}
} catch (UnexpectedValueException $e) {
	echo $pdo->log->error($e->getMessage());
}

// Delete all covers, previews, samples
echo $pdo->log->header("Deleting all images, previews and samples that still remain.");
try {
	$dirItr = new \RecursiveDirectoryIterator(nZEDb_COVERS);
	$itr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::LEAVES_ONLY);
	foreach ($itr as $filePath) {
		if (basename($filePath) != '.gitignore' && basename($filePath) != 'no-cover.jpg' && basename($filePath) != 'no-backdrop.jpg') {
			/** @scrutinizer ignore-unhandled */
			@unlink($filePath);
		}
	}
} catch (UnexpectedValueException $e) {
	echo $pdo->log->error($e->getMessage());
}

echo $pdo->log->header("Deleted all releases, images, previews and samples. This script ran for " . $consoletools->convertTime(TIME() - $timestart));
