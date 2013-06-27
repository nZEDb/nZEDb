<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");

$pieces = explode("                       ", $argv[1]);
$postprocess = new PostProcess(true);
if (isset($pieces[6]))
{
	$postprocess->processAdditional($argv[1]);
}
elseif (isset($pieces[3]))
{
    $postprocess->processNfos($argv[1]);
}
elseif (isset($pieces[2]))
{
    $postprocess->processMovies($argv[1]);
}
elseif (isset($pieces[1]))
{
    $postprocess->processTv($argv[1]);
}
