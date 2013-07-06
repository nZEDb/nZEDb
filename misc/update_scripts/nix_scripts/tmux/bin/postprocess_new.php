<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/site.php");

//remove folders from tmpunrar
$site = new Sites();
$tmpunrar = $site->get()->tmpunrarpath;
if ((count(glob("$tmpunrar/*",GLOB_ONLYDIR))) > 0)
{
    echo "Removing dead folders from ".$tmpunrar."\n";
    exec("rm -rf ".$tmpunrar."/*");
}

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
