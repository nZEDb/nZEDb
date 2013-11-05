<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");

if (isset($argv[1]) && is_numeric($argv[1]))
{
	$postprocess = new PostProcess(true);
	$postprocess->processMovies($argv[1]);
	$postprocess->processAnime($argv[1]);
	$postprocess->processTV($argv[1], 0);
	$postprocess->processXXX($argv[1], 0);
	$postprocess->processPC($argv[1], 0);
}
