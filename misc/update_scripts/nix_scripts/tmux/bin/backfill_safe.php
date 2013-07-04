<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	if (isset($pieces[3]))
	{
		$backfill = new Backfill();
		//usleep(3500000);
		$backfill->getRange($pieces[0], $pieces[1], $pieces[2], $pieces[3]);
	}
	elseif (isset($pieces[2]))
	{
		$backfill = new Backfill();
		$backfill->getFinal($pieces[0], $pieces[1]);
	}
	elseif (isset($pieces[1]))
	{
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups($pieces[0], $pieces[1], "groupname");
	}
}
