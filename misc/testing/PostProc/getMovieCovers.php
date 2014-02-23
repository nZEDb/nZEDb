<?php

//This script will update all records in the movieinfo table where there is no cover

require_once dirname(__FILE__) . '/../../../www/config.php';

$movie = new Movie(true);
$db = new Db();
$c = new ColorCLI();

$movies = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE cover = 0 ORDER BY year ASC, id DESC");
if ($movies->rowCount() > 0) {
	echo $c->primary("Updating " . number_format($movies->rowCount()) . " movie covers.");
}

foreach ($movies as $mov) {
	$starttime = microtime(true);
	$mov = $movie->updateMovieInfo($mov['imdbid']);

	// tmdb limits are 30 per 10 sec, not certain for imdb
	$diff = floor((microtime(true) - $starttime) * 1000000);
	if (333333 - $diff > 0) {
		echo "sleeping\n";
		usleep(333333 - $diff);
	}
}
echo "\n";
