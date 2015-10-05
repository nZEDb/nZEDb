<?php
//This script extracts TV covers from the database (tvrage_titles.imgdata) and saves them to the tvrage covers directory
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;

$pdo = new Settings();
$shows = $pdo->queryDirect("SELECT rageid, imgdata FROM tvrage_titles WHERE rageid > 0");

if ($shows instanceof \Traversable) {
	echo "\n";
	$imgSavePath = nZEDb_COVERS . 'tvrage' . DS;
	echo $pdo->log->header("Extracting " . number_format($shows->rowCount()) . " tv show covers and saving them to ${imgSavePath}." . PHP_EOL);

	$failCnt = $succCnt = $badCnt = 0;

	foreach ($shows as $show) {

		if (!empty($show['imgdata']) && !is_null($show['imgdata'])) {

			// Store it on the hard drive.
			$coverPath = $imgSavePath . (int)$show['rageid'] . '.jpg';
			$coverSave = @file_put_contents($coverPath, $show['imgdata']);

			// Check if it's on the drive.
			if ($coverSave === false || !is_file($coverPath)) {
				$failCnt++;
				echo ".";
			// Check if the image is formatted properly and useable
			} else if (imagecreatefromjpeg($coverPath) === false) {
				@unlink($coverPath);
				$failCnt++;
				echo "#";
			} else {
				$pdo->queryExec(
						sprintf("
							UPDATE tvrage_titles
							SET hascover = 1
							WHERE rageid = %d",
							$show['rageid']
						)
				);
				$succCnt++;
				echo "!";
			}
		} else {
			$badCnt++;
			echo "!";
		}
	}
	echo PHP_EOL . PHP_EOL . $pdo->log->header("Successfully exported " . number_format($succCnt) . " tv show covers. " . number_format($failCnt) . " failed to save and " . number_format($badCnt) . " entries had bad imgdata." . PHP_EOL);
} else {
	exit(PHP_EOL . $pdo->log->error("No shows found to extract images."));
}
