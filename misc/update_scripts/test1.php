<?php

require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/groups.php");

$groupName = isset($argv[3]) ? $argv[3] : "";

$i = 0;
while( $i == 0 )
{
	$releases = new Releases;
	$releases->processReleasesStage1($groupName);
    $releases->processReleasesStage2($groupName);
    $releases->processReleasesStage3($groupName);
    $releases->processReleasesStage4($groupName);
	sleep(10);
}


?>
