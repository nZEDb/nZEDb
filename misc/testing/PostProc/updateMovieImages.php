<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$covers = $updated = $deleted = 0;

if ($argc == 1 || $argv[1] != 'true') {
	exit($pdo->log->error("\nThis script will check all images in covers/movies and compare to db->movieinfo.\nTo run:\nphp $argv[0] true\n"));
}

$row = $pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'coverspath'");
if ($row !== false) {
	\nzedb\utility\Utility::setCoversConstant($row['value']);
} else {
	die("Unable to set Covers' constant!\n");
}
$path2covers = nZEDb_COVERS . 'movies' . DS;

$dirItr = new \RecursiveDirectoryIterator($path2covers);
$itr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($itr as $filePath) {
	if (is_file($filePath) && preg_match('/-cover\.jpg/', $filePath)) {
		preg_match('/(\d+)-cover\.jpg/', basename($filePath), $match);
		if (isset($match[1])) {
			$run = $pdo->queryDirect("UPDATE movieinfo SET cover = 1 WHERE cover = 0 AND imdbid = " . $match[1]);
			if ($run->rowCount() >= 1) {
				$covers++;
			} else {
				$run = $pdo->queryDirect("SELECT imdbid FROM movieinfo WHERE imdbid = " . $match[1]);
				if ($run->rowCount() == 0) {
					echo $pdo->log->info($filePath . " not found in db.");
				}
			}
		}
	}
	if (is_file($filePath) && preg_match('/-backdrop\.jpg/', $filePath)) {
		preg_match('/(\d+)-backdrop\.jpg/', basename($filePath), $match1);
		if (isset($match1[1])) {
			$run = $pdo->queryDirect("UPDATE movieinfo SET backdrop = 1 WHERE backdrop = 0 AND imdbid = " . $match1[1]);
			if ($run->rowCount() >= 1) {
				$updated++;
				printf("UPDATE movieinfo SET backdrop = 1 WHERE backdrop = 0 AND imdbid = " . $match1[1] . "\n");
			} else {
				$run = $pdo->queryDirect("SELECT imdbid FROM movieinfo WHERE imdbid = " . $match1[1]);
				if ($run->rowCount() == 0) {
					echo $pdo->log->info($filePath . " not found in db.");
				}
			}
		}
	}
}

$qry = $pdo->queryDirect("SELECT imdbid FROM movieinfo WHERE cover = 1");
if ($qry instanceof \Traversable) {
	foreach ($qry as $rows) {
		if (!is_file($path2covers . $rows['imdbid'] . '-cover.jpg')) {
			$pdo->queryDirect("UPDATE movieinfo SET cover = 0 WHERE cover = 1 AND imdbid = " . $rows['imdbid']);
			echo $pdo->log->info($path2covers . $rows['imdbid'] . "-cover.jpg does not exist.");
			$deleted++;
		}
	}
}
$qry1 = $pdo->queryDirect("SELECT imdbid FROM movieinfo WHERE backdrop = 1");
if ($qry1 instanceof \Traversable) {
	foreach ($qry1 as $rows) {
		if (!is_file($path2covers . $rows['imdbid'] . '-backdrop.jpg')) {
			$pdo->queryDirect("UPDATE movieinfo SET backdrop = 0 WHERE backdrop = 1 AND imdbid = " . $rows['imdbid']);
			echo $pdo->log->info($path2covers . $rows['imdbid'] . "-backdrop.jpg does not exist.");
			$deleted++;
		}
	}
}
echo $pdo->log->header($covers . " covers set.");
echo $pdo->log->header($updated . " backdrops set.");
echo $pdo->log->header($deleted . " movies unset.");
