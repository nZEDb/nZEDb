<?php

require(dirname(__FILE__)."/../config.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/groups.php");


$groups = new Groups();
$n = "\n";
$groupID = "";

if (!empty($argv[1]))
{
	$groupInfo = $groups->getByName($argv[1]);
	$groupID = $groupInfo['ID'];
}

$releases = new Releases;
if (isset($argv[1]) && ($argv[1] == "1" ))
	$releases->processReleasesStage1($groupID);
if (isset($argv[1]) && ($argv[1] == "2" ))
	$releases->processReleasesStage2($groupID);
if (isset($argv[1]) && ($argv[1] == "3" ))
	$releases->processReleasesStage3($groupID);
if (isset($argv[1]) && ($argv[1] == "4" ))
	$releases->processReleasesStage4_loop($groupID);
if (isset($argv[1]) && ($argv[1] == "5" ))
	$releases->processReleasesStage4dot5($groupID);
if (isset($argv[1]) && ($argv[1] == "6" ))
	$releases->processReleasesStage5_loop($groupID);
if (isset($argv[1]) && ($argv[1] == "7" ))
	$releases->processReleasesStage6(1,2,$groupID);
if (isset($argv[1]) && ($argv[1] == "8" ))
	$releases->processReleasesStage7($groupID);


?>

