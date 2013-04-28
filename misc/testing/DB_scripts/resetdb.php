<?php

//
//	This script removes all releases, nzb files, truncates all article tables, resets groups.
//

if (isset($argv[1]) && $argv[1] === "true")
{
	define('FS_ROOT', realpath(dirname(__FILE__)));
	require_once(FS_ROOT."/../../../www/config.php");
	require_once(FS_ROOT."/../../../www/lib/framework/db.php");
	require_once(FS_ROOT."/../../../www/lib/releases.php");

	$db = new Db;
	$timestart = TIME();
	$relcount = 0;

	echo "Truncating tables.\n";
	$db->query("truncate table collections");
	$db->query("truncate table binaries");
	$db->query("truncate table parts");
	$db->query("truncate table partrepair");
	$db->query("truncate table releasenfo");

	echo "Resetting groups.\n";
	$db->query("UPDATE groups SET first_record=0, first_record_postdate=NULL, last_record=0, last_record_postdate=NULL");

	echo "Deleting Releases and NZB's.\n";
	$relids = $db->query(sprintf("SELECT ID FROM releases"));
	$releases = new Releases();

	foreach ($relids as $relid)
	{
		$releases->delete($relid['ID']);
		$relcount++;
	}

	echo "Deleted ".$relcount." release(s). This script ran for ";
	echo TIME() - $timestart;
	echo " second(s).\n";
}
else
{
	exit("This script removes all releases, nzb files, truncates all article tables, resets groups.\nIf you are sure you want to run it, type php resetdb.php true\n");
}
?>
