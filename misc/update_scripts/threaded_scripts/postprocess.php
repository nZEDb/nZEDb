<?php

require(dirname(__FILE__)."/../config.php");
require(WWW_DIR."/lib/postprocess.php");

if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "nfos")
{
	$postprocess = new PostProcess(true);
	$postprocess->processNfos();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "movies")
{
	$postprocess = new PostProcess(true);
	$postprocess->processMovies();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "music")
{
	$postprocess = new PostProcess(true);
	$postprocess->processMusic();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "games")
{
	$postprocess = new PostProcess(true);
	$postprocess->processGames();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "anime")
{
	$postprocess = new PostProcess(true);
	$postprocess->processAnime();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "tv")
{
	$postprocess = new PostProcess(true);
	$postprocess->processTV();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processAdditional();
    $postprocess->processNfos();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "books")
{
    $postprocess = new PostProcess(true);
    $postprocess->processBooks();
}
