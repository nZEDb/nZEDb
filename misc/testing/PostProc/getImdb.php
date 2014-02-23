<?php

//This script will update all records in the movieinfo table

require_once dirname(__FILE__) . '/../../../www/config.php';

$movie = new Movie(true);
$db = new Db();
$c = new ColorCLI();

$movies = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE tmdbid IS NULL ORDER BY id ASC");
if ($movies->rowCount() > 0) {
	echo $c->header("Updating movie info for " . number_format($movies->rowCount()) . " movies.");

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
}
