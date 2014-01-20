<?php
//This script will update all records in the movieinfo table where there is no cover

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'movie.php';

$movie = new Movie(true);
$db = new Db();

$movies = $db->queryDirect("SELECT imdbid FROM movieinfo WHERE cover = 0 ORDER BY year DESC, id DESC");
if ($movies->rowCount() > 0)
    echo "Updating ".$movies->rowCount()." movie covers.\n";

foreach ($movies as $mov)
{
    $starttime = microtime(true);
    $mov = $movie->updateMovieInfo($mov['imdbid']);
    echo ".";
    $diff = floor((microtime(true) - $starttime) * 1000000);
    if (1000000 - $diff > 0)
    {
        echo "sleeping\n";
        usleep(1000000 - $diff);
    }
}
echo "\n";
