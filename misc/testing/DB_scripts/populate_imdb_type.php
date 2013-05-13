<?php
/*
 * Updates the movie type for movies.
 */
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/movie.php");

$movie = new Movie(true);
$db = new DB();

$query = "SELECT * FROM movieinfo";
$res = $db->queryDirect($query);

while ($rel =  $db->fetchAssoc($res))
{
	$movie->updateMovieInfo($rel['imdbID']);
}
?>
