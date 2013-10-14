<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/tmux.php");

$tmux = new Tmux;
$torun = $tmux->get()->POST;

$pieces = explode("           =+=            ", $argv[1]);
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
	echo ".";
}
elseif (isset($pieces[1]))
{
    if($postprocess->checkIfAnime($argv[1]))
	    $postprocess->processSingleAnime($argv[1]);
    else	
	    $postprocess->processTv($argv[1]);
}
