<?php

require("config.php");
require_once(WWW_DIR."/lib/postprocess.php");

echo "\nThis script post processes all releases on an endless loop, waits 20 seconds in between loops.\n\n";

$i=1;
while($i=1)
{
    $postprocess = new PostProcess(true);
    $postprocess->processMovies();
    $postprocess->processMusic();
    $postprocess->processGames();
    $postprocess->processAnime();
    $postprocess->processTv();
    $postprocess->processAdditional();
}

