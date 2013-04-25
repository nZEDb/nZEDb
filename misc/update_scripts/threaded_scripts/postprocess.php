<?php

require(dirname(__FILE__)."/../config.php");
require_once(WWW_DIR."/lib/postprocess.php");

if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "nfo")
{
	$postprocess = new PostProcess(false);
	$postprocess->processNfos();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "movies")
{
	$postprocess = new PostProcess(false);
	$postprocess->processMovies();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "music")
{
	$postprocess = new PostProcess(false);
	$postprocess->processMusic();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "games")
{
	$postprocess = new PostProcess(false);
	$postprocess->processGames();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "anime")
{
	$postprocess = new PostProcess(false);
	$postprocess->processAnime();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "tv")
{
	$postprocess = new PostProcess(false);
	$postprocess->processTV();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "additional")
{
	$postprocess = new PostProcess(false);
	$postprocess->processAdditional();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "book")
{
    $postprocess = new PostProcess(false);
    $postprocess->processBooks();
}
