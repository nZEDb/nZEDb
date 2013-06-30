<?php

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

//
//	This script removes all releases and nzb files based on poster, searchname, name or guid
//
if (sizeof($argv) == 4)
{
	if ($argv[2] == "equals" && ($argv[1] == "searchname" || $argv[1] == "name" || $argv[1] == "guid" || $argv[1] == "poster"))
	{
		$relids = $db->query(sprintf("SELECT ID, guid FROM releases where %s = %s", $argv[1], $db->escapeString($argv[3])));
		printf("SELECT ID, fuck, guid FROM releases where %s = %s", $argv[1], $db->escapeString($argv[3]));
	}
	elseif ($argv[2] == "like" && ($argv[1] == "searchname" || $argv[1] == "name" || $argv[1] == "guid" || $argv[1] == "poster"))
	{
		$relids = $db->query("SELECT ID, guid FROM releases where ".$argv[1]." like '%".$argv[3]."%'");
		print("SELECT ID, guid FROM releases where ".$argv[1]." like '%".$argv[3]."%'");
	}
	else
	{
		exit("This script removes all releases and nzb files from a poster or by searchname, name or guid.\nIf you are sure you want to run it, type php delete_releases.php [ poster, searchname, name, guid ] equals [ name/guid ]\nYou can also use like instead of = by doing type php delete_releases.php [ poster, searchname, name, guid ] like [ name/guid ]\n");
	}
	echo "\nDeleting ".sizeof($relids)." releases and NZB's for ".$argv[3]."\n";
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
	exit("This script removes all releases and nzb files from a poster or by searchname, name or guid.\nIf you are sure you want to run it, type php delete_releases.php [ poster, searchname, name, guid ] equals [ name/guid ]\nYou can also use like instead of = by doing type php delete_releases.php [ poster, searchname, name, guid ] like [ name/guid ]\n");
}
