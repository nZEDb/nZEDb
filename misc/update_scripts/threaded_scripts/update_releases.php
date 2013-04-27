<?php

require(dirname(__FILE__)."/../config.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/framework/db.php");


$db = new DB();
$groups = new Groups();
$page = new Page();
$n = "\n";
$groupID = "";

if (!empty($groupName))
{
	$groupInfo = $groups->getByName($groupName);
	$groupID = $groupInfo['ID'];
}

$releases = new Releases;
$releases->processReleasesStage1($groupID);
$releases->processReleasesStage2($groupID);
$releases->processReleasesStage3($groupID);
$releases->processReleasesStage4($groupID);
$releases->processReleasesStage4dot5($groupID);
$releases->processReleasesStage5($groupID);
$releases->processReleasesStage6(1,2,$groupID);
$releases->processReleasesStage7($groupID);


?>
