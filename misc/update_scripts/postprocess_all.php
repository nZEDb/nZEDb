<?php

require("config.php");
require_once(WWW_DIR."/lib/postprocess.php");

echo "\nThis script post processes all but NFOs.\n\n";

$postprocess = new PostProcess(true);
$postprocess->processMovies();
$postprocess->processMusic();
$postprocess->processGames();
$postprocess->processAnime();
$postprocess->processTv();
$postprocess->processAdditional();

