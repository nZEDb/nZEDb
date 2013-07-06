<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/tmux.php");

$tmux = new Tmux;
$torun = $tmux->get()->POST;

$pieces = explode("                       ", $argv[1]);
$postprocess = new PostProcess(true);
if (isset($pieces[6]) && ($torun == "1" || $torun == "3"))
{
	//remove folders from tmpunrar
	$site = new Sites();
	$tmpunrar = $site->get()->tmpunrarpath;
	if ((count(glob("$tmpunrar/*",GLOB_ONLYDIR))) > 0)
	{
		echo "Removing dead folders from ".$tmpunrar."\n";
		exec("rm -rf ".$tmpunrar."/*");
	}
	$postprocess->processAdditional($argv[1]);
}
elseif (isset($pieces[3]) && ($torun == "2" || $torun == "3"))
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
