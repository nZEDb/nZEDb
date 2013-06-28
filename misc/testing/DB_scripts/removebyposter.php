<?php

//
//	This script removes all releases, nzb files, truncates all article tables, resets groups.
//

if (isset($argv[1]))
{
	define('FS_ROOT', realpath(dirname(__FILE__)));
	require_once(FS_ROOT."/../../../www/config.php");
	require_once(FS_ROOT."/../../../www/lib/framework/db.php");
	require_once(FS_ROOT."/../../../www/lib/releases.php");
	require_once(FS_ROOT."/../../../www/lib/site.php");
	require_once(FS_ROOT."/../../../www/lib/consoletools.php");

	$db = new Db;
	$s = new Sites();
	$consoletools = new ConsoleTools();
	$site = $s->get();
	$timestart = TIME();
	$relcount = 0;

	$query = "SELECT ID, guid FROM releases where fromname ='".$argv[1]."'";
	echo "$query\n";
	$relids = $db->query($query);
	echo "Deleting ".sizeof($relids)." releases and NZB's for ".$argv[1]."\n";
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
	exit("This script removes all releases, nzb files, truncates all article tables, resets groups belonging to a poster.\nIf you are sure you want to run it, type php resetdb.php poster\n");
}
?>
