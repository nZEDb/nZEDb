<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/binaries.php");
require_once(WWW_DIR."lib/groups.php");

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	if (isset($pieces[3]))
	{
		$backfill = new Backfill();
		$backfill->getRange(trim($pieces[0],"'"), $pieces[1], $pieces[2], $pieces[3]);
	}
	elseif (isset($pieces[2]))
	{
		$backfill = new Backfill();
		$backfill->getFinal(trim($pieces[0],"'"), $pieces[1], $pieces[2]);
	}
	elseif (isset($pieces[1]) && $pieces[0] == "binupdate")
	{
		$binaries = new Binaries();
		$grp = new Groups();
		$group = $grp->getByName($pieces[1]);
		$binaries->updateGroup($group);
	}
	elseif (isset($pieces[1]))
	{
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups(trim($pieces[0],"'"), $pieces[1]);
	}
}
