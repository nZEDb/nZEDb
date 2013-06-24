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
    exec("rm -r ".$tmpunrar."/*");
}

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
