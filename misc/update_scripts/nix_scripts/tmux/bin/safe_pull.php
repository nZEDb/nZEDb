<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	//printf("%s, %s, %s, %s\n", trim($pieces[0],"'"), trim($pieces[1],"'"), trim($pieces[2],"'"), trim($pieces[3],"'"));
	//exit();
	if (isset($pieces[3]))
	{
		$backfill = new Backfill();
		//usleep(3500000);
		$backfill->getRange(trim($pieces[0],"'"), trim($pieces[1],"'"), trim($pieces[2],"'"), trim($pieces[3],"'"));
	}
	elseif (isset($pieces[2]))
	{
		$backfill = new Backfill();
		$backfill->getFinal(trim($pieces[0],"'"), trim($pieces[1],"'"), trim($pieces[2],"'"));
	}
	elseif (isset($pieces[1]))
	{
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups(trim($pieces[0],"'"), trim($pieces[1],"'"));
	}
}
