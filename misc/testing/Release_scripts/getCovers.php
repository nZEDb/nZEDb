<?php
//This script will update all records in the movieinfo table

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/movie.php");

$movie = new Movie(true);

$db = new Db;

$movies = $db->query("SELECT imdbID from movieinfo where cover = 0");

foreach ($movies as $mov) {
	$mov = $movie->updateMovieInfo($mov['imdbID']);
	sleep(1);
}

?>
