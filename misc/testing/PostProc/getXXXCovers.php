<?php
//This script will update all records in the xxxinfo table where there is no cover
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$movie = new XXX(true);
$pdo = new Settings();
$c = new ColorCLI();

$movies = $pdo->queryDirect("SELECT id FROM xxxinfo WHERE cover = 0");
if ($movies->rowCount() > 0) {
	echo $c->primary("Updating " . number_format($movies->rowCount()) . " movie covers.");
}

foreach ($movies as $mov) {
	$starttime = microtime(true);
	$mov = $movie->updateXXXInfo($mov['id']);

	// sleep so that it's not ddos' the site
	$diff = floor((microtime(true) - $starttime) * 1000000);
	if (333333 - $diff > 0) {
		echo "\nsleeping\n";
		usleep(333333 - $diff);
	}
}
echo "\n";
