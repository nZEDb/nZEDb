<?php
//This script will update all records in the movieinfo table

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'movie.php';

$movie = new Movie(true);
$db = new Db();

$movies = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE cover = 0");
if ($movies->rowCount() > 0)
	echo "Updating ".$movies->rowCount()." movie covers.\n";

foreach ($movies as $mov)
{
	$mov = $movie->updateMovieInfo($mov['imdbid']);
	echo ".";
	sleep(1);
}
echo "\n";
?>
