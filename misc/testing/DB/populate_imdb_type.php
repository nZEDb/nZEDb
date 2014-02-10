<?php
// Update info for the imdb ID.

require_once dirname(__FILE__) . '/../../../www/config.php';

$movie = new Movie(true);
$db = new DB();

if (!isset($argv[1])) {
	exit("This script fetches missing info for IMDB id's from tmdb and imdb.\nTo run it pass true as an argument.\n");
}

$res = $db->query("SELECT imdbid FROM movieinfo");
if (count($res) > 0)
{
	foreach ($res as $row)
		$movie->updateMovieInfo($row['imdbid']);
}
