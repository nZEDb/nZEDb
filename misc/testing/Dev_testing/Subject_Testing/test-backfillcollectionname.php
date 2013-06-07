<?php
require("../../../../www/config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/groups.php");

if(!isset($argv[1]))
	exit('You must start the script like this (# of articles) : php test-backfillcleansubject.php 20000'."\n");
else
{
	$groups = new Groups();
	$grouplist = $groups->getActive();
	foreach ($grouplist as $name)
		dogroup($name["name"], $argv[1]);
}

function dogroup($name, $articles)
{
	$backfill = new backfill();
	$backfill->backfillPostAllGroups($name, $articles);
	echo "Type y and press enter to continue, n to quit.\n";
	if(trim(fgets(fopen("php://stdin","r"))) == 'y')
		return true;
	else
		exit("Done.\n");
}

?>
