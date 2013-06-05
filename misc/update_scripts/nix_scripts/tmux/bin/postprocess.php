<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");

if (isset($argv[1]) && is_numeric($argv[1]))
{
	$postprocess = new PostProcess(true);
	$postprocess->processPredb();
	$postprocess->processAdditional($argv[1]);
	$postprocess->processNfos($argv[1]);
	$postprocess->processBooks($argv[1]);
	$postprocess->processMovies($argv[1]);
	$postprocess->processMusic($argv[1]);
	$postprocess->processGames($argv[1]);
	$postprocess->processAnime($argv[1]);
	$postprocess->processTV($argv[1]);
}
