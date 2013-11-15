<?php
//This script will update all records in the movieinfo table

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_WWW . 'config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'movie.php';

$movie = new Movie(true);
$db = new Db();

$movies = $db->query("SELECT imdbid FROM movieinfo");

foreach ($movies as $mov)
{
	$mov = $movie->updateMovieInfo($mov['imdbid']);
	sleep(1);
}

?>
