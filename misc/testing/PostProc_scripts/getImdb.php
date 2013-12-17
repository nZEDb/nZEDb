<?php
//This script will update all records in the movieinfo table

require_once dirname(__FILE__) . '/www/config.php';
require_once nZEDb_WWW . 'config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'movie.php';

$movie = new Movie(true);
$db = new Db();

$movies = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE tmdbid IS NULL ORDER BY id DESC");
if ($movies->rowCount() > 0)
	echo "Updating movie info for ".$movies->rowCount()." movies.\n";
foreach ($movies as $mov)
{
	$starttime = microtime(true);
	$mov = $movie->updateMovieInfo($mov['imdbid']);
	if (!$mov)
		echo ".";
	// tmdb limits are 30 per 10 sec, not certain for imdb
	$diff = floor((microtime(true) - $starttime) * 1000000);
	if (333333 - $diff > 0)
	{
		echo "sleeping\n";
		usleep(333333 - $diff);
	}
}
echo "\n";
?>
