<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");

if (isset($argv[1]) && is_numeric($argv[1]))
{
	$postprocess = new PostProcess(true);
	$postprocess->processMovies($argv[1]);
	$postprocess->processAnime($argv[1]);
	if($postprocess->checkIfAnime($argv[1]))
		$postprocess->processSingleAnime($argv[1]);
	else
		$postprocess->processTv($argv[1]);
}
