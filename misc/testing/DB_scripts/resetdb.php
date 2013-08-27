<?php
// This script removes all releases, nzb files, truncates all article tables, resets groups.

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
	$db->query("TRUNCATE TABLE collections");
	$db->query("TRUNCATE TABLE binaries");
	$db->query("TRUNCATE TABLE parts");
	$db->query("TRUNCATE TABLE partrepair");
	$db->query("TRUNCATE TABLE releasenfo");
	$db->query("TRUNCATE TABLE nzbs");

	echo "Resetting groups.\n";
	$db->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL");

	$relids = $db->query(sprintf("SELECT id, guid FROM releases"));
	if (count($relids) > 0)
	{
		echo "Deleting ".count($relids)." releases, NZB's, previews and samples.\n";
		$releases = new Releases();

		foreach ($relids as $relid)
		{
			$releases->fastDelete($relid['id'], $relid['guid'], $site);
			$relcount++;
			$consoletools->overWrite("Deleting:".$consoletools->percentString($relcount,sizeof($relids))." Time:".$consoletools->convertTimer(TIME() - $timestart));
		}
	}
	if ($relcount > 0)
		echo "\n";
	echo "Deleted ".$relcount." release(s). This script ran for ";
	echo $consoletools->convertTime(TIME() - $timestart);
	exit(".\n");
}
else
	exit("This script removes all releases, nzb files, truncates all article tables, resets groups.\nIf you are sure you want to run it, type php resetdb.php true\n");
?>
