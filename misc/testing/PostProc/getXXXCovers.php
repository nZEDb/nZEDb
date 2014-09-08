<?php
//This script will update all records in the xxxinfo table where there is no cover
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

$movie = new \XXX(['Echo' => true, 'Settings' => $pdo]);

$movies = $pdo->queryDirect("SELECT title FROM xxxinfo WHERE cover = 0");
if ($movies instanceof \Traversable) {
	echo $pdo->log->primary("Updating " . number_format($movies->rowCount()) . " movie covers.");
	foreach ($movies as $mov) {
		$starttime = microtime(true);
		echo $pdo->log->primaryOver("Looking up: " . $pdo->log->headerOver($mov['title'])) . "\n";
		$mov = $movie->updateXXXInfo($mov['title']);

		// sleep so that it's not ddos' the site
		$diff = floor((microtime(true) - $starttime) * 1000000);
		if (333333 - $diff > 0) {
			echo "\nsleeping\n";
			usleep(333333 - $diff);
		}
	}
	echo "\n";
}
