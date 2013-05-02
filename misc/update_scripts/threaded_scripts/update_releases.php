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
$releases->processReleasesStage1($groupID);
$releases->processReleasesStage2($groupID);
$releases->processReleasesStage3($groupID);
$releases->processReleasesStage4($groupID);
$releases->processReleasesStage4dot5($groupID);
$releases->processReleasesStage5($groupID);
$releases->processReleasesStage4($groupID);
$releases->processReleasesStage5($groupID);
$releases->processReleasesStage6(1,2,$groupID);
$releases->processReleasesStage7($groupID);


?>
