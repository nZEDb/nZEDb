<?php

//
//	This script removes all releases, nzb files, truncates all article tables, resets groups.
//

if (isset($argv[1]) && $argv[1] === "true")
{
	require_once(dirname(__FILE__)."/../../../www/config.php");
	require_once(WWW_DIR."lib/framework/db.php");
	require_once(WWW_DIR."lib/releases.php");
	require_once(WWW_DIR."lib/site.php");
	require_once(WWW_DIR."lib/consoletools.php");

	$db = new Db();
	$s = new Sites();
	$consoletools = new ConsoleTools();
	$site = $s->get();
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
	echo "Set releaseID's in predb to null.\n";
	$db->query("UPDATE predb SET releaseID=NULL");

	$relids = $db->query(sprintf("SELECT ID, guid FROM releases"));
	echo "Deleting ".sizeof($relids)." releases and NZB's.\n";
	$releases = new Releases();

	foreach ($relids as $relid)
	{
		$releases->fastDelete($relid['ID'], $relid['guid'], $site);
		$relcount++;
		$consoletools->overWrite("Deleting:".$consoletools->percentString($relcount,sizeof($relids))." Time:".$consoletools->convertTimer(TIME() - $timestart));
	}

	if ($relcount > 0)
		echo "\n";
	echo "Deleted ".$relcount." release(s). This script ran for ";
	echo $consoletools->convertTime(TIME() - $timestart);
	echo ".\n";
}
else
{
	exit("This script removes all releases, nzb files, truncates all article tables, resets groups.\nIf you are sure you want to run it, type php resetdb.php true\n");
}
?>
