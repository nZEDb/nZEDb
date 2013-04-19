<?php

require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/backfill.php");

$n = "\n";
	
if (isset($argv[1]) && $argv[1] == "all" && $argv[1] !== "safe" && !is_numeric($argv[1]) && !isset($argv[2]))
{
	$backfill = new Backfill();
	$groupName = '';
	$backfill->backfillAllGroups($groupName);
}
else if (isset($argv[1]) && $argv[1] !== "all" && $argv[1] !== "safe" && !is_numeric($argv[1]) && isset($argv[2]) && is_numeric($argv[2]))
{
	$backfill = new Backfill();
	$backfill->backfillPostAllGroups($argv[1], $argv[2]);
}
else if (isset($argv[1]) && $argv[1] !== "all" && $argv[1] !== "safe" && !is_numeric($argv[1]) && !isset($argv[2]))
{
	$backfill = new Backfill();
	$backfill->backfillAllGroups($argv[1]);
}
else if (isset($argv[1]) && $argv[1] !== "all" && $argv[1] !== "safe" && is_numeric($argv[1]) && !isset($argv[2]))
{
	$backfill = new Backfill();
	$groupName = '';
	$backfill->backfillPostAllGroups($groupName, $argv[1]);
}
else if (isset($argv[1]) && $argv[1] !== "all" && $argv[1] == "safe" && !is_numeric($argv[1]) && isset($argv[2]) && is_numeric($argv[2]))
{
	$backfill = new Backfill();
	$backfill->safeBackfill($argv[2]);
}
else
{
	exit("ERROR: Wrong set of arguments.".$n.
		 "php backfill.php all				...: Backfills all groups 1 at a time, by date (set in admin-view groups)".$n.
		 "php backfill.php alt.binaries.ath		...: Backfills a group by name, by date (set in admin-view groups)".$n.
		 "php backfill.php 200000 			...: Backfills all groups by number of articles".$n.
		 "php backfill.php alt.binaries.ath 200000 	...: Backfills a group by name by number of articles".$n.
		 "php backfill.php safe 200000			...: Backfill all groups x articles, then stops, until all groups have reached 2012-06-24.".$n);		 
}

?>
