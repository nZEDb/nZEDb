<?php
require_once dirname(dirname(__DIR__)) . '/www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (!isset($argv[1]) || $argv[1] != 'true') {
	exit($pdo->log->error("\nThis script will download all tvrage shows and insert into the db.\n\n"
						  . "php $argv[0] true    ...: To run.\n"));
}

$newnames = $updated = 0;

echo "Attempting to fetch data file from TVRage...\n";
$tvshows = @simplexml_load_file('http://services.tvrage.com/feeds/show_list.php');
if ($tvshows !== false) {
	echo "Starting to process file entries...\n";
	foreach ($tvshows->show as $rage) {
		echo "RageID: " . $rage->id . ", name: " . $rage->name . " - ";
		$dupecheck = $pdo->queryOneRow(sprintf('SELECT COUNT(id) AS count FROM tvrage_titles WHERE rageid = %s',
											   $pdo->escapeString($rage->id)));

		if (isset($rage->id) && isset($rage->name) && !empty($rage->id) && !empty($rage->name) &&
			$dupecheck !== false && $dupecheck['count'] == 0) {
			$pdo->queryInsert(sprintf('INSERT INTO tvrage_titles (rageid, releasetitle, country) VALUES (%s, %s, %s)',
									  $pdo->escapeString($rage->id),
									  $pdo->escapeString($rage->name),
									  $pdo->escapeString($rage->country)
							  ));
			$updated++;
			echo "added\n";
		} else {
			echo "FAILED!!\n";
		}
	}
} else {
	exit($pdo->log->info("TVRage site has a hard limit of 400 concurrent API requests. At the moment, they have reached that limit. Please wait before retrying\n"));
}

if ($updated != 0) {
	echo $pdo->log->info("Inserted " . $updated .
						 " new shows into the TvRage table.  To fill out the newly populated TvRage table\n"
						 . "php misc/testing/PostProcess/updateTvRage.php\n");
} else {
	echo "\n";
	echo $pdo->log->info("TvRage database is already up to date!\n");
}

?>
