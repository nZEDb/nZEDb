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

//	This script removes all releases and nzb files based on poster, searchname, name, groupname, or guid.
if (sizeof($argv) == 4)
{
	if ($argv[2] == "equals" && ($argv[1] == "searchname" || $argv[1] == "name" || $argv[1] == "guid" || $argv[1] == "fromname"))
	{
		$relids = $db->query(sprintf("SELECT id, guid FROM releases WHERE %s = %s", $argv[1], $db->escapeString($argv[3])));
		printf("SELECT id, guid FROM releases WHERE %s = %s", $argv[1], $db->escapeString($argv[3]));
	}
    elseif ($argv[2] == "equals" && ($argv[1] == "groupname"))
    {
        $relids = $db->query(sprintf("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID  AND g.name = %s", $db->escapeString($argv[3])));
        printf("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID  AND g.name = %s", $db->escapeString($argv[3]));
    }
	elseif ($argv[2] == "like" && ($argv[1] == "searchname" || $argv[1] == "name" || $argv[1] == "guid" || $argv[1] == "fromname"))
	{
		$like = ' ILIKE';
		if ($db->dbSystem() == 'mysql')
			$like = ' LIKE';
		$relids = $db->query("SELECT id, guid FROM releases WHERE ".$argv[1].$like." '%".$argv[3]."%'");
		printf("SELECT id, guid FROM releases WHERE ".$argv[1].$like." '%".$argv[3]."%'");
	}
    elseif ($argv[2] == "like" && $argv[1] == "groupname")
    {
        $like = ' ILIKE';
        if ($db->dbSystem() == 'mysql')
            $like = ' LIKE';
        $relids = $db->query("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND g.name ".$like." '%".$argv[3]."%'");
        printf("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND g.name ".$like." '%".$argv[3]."%'");
    }
    elseif ($argv[2] == "equals" && ($argv[1] == "adddate" || $argv[1] == "postdate") && isset($argv[3]) && is_numeric($argv[3]))
    {
		if ($db->dbSystem() == 'mysql')
		{
			$relids = $db->query("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r.".$argv[1]." > NOW() - INTERVAL ".$argv[3]." DAY");
			printf("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r.".$argv[1]." > NOW() - INTERVAL ".$argv[3]." DAY");
		}
		else
		{
			$relids = $db->query("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r.".$argv[1]." > NOW() - INTERVAL '".$argv[3]." DAYS'");
			printf("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r.".$argv[1]." > NOW() - INTERVAL '".$argv[3]." DAYS'");
		}
	}
	else
		exit("This script removes all releases and nzb files from a poster or by searchname, name, groupname, guid or newer than date(using adddate).\nIf you are sure you want to run it, type php delete_releases.php [ fromname, searchname, name, groupname, guid ] equals [ name/guid ]\nYou can also use like instead of = by doing type php delete_releases.php [ fromname, searchname, name, groupname, guid ] like [ name/guid ]\n");

	if ($argv[1] == "adddate")
		echo "\nDeleting ".sizeof($relids)." releases and NZB's for past ".$argv[3]." days\n";
	else
		echo "\nDeleting ".sizeof($relids)." releases and NZB's for ".$argv[3]."\n";

	$releases = new Releases();
	foreach ($relids as $relid)
	{
		$releases->fastDelete($relid['id'], $relid['guid'], $site);
		$relcount++;
		$consoletools->overWrite("Deleting: ".$consoletools->percentString($relcount,sizeof($relids))." Time:".$consoletools->convertTimer(TIME() - $timestart));
	}

	if ($relcount > 0)
		echo "\n";
	echo "Deleted ".$relcount." release(s). This script ran for ";
	echo $consoletools->convertTime(TIME() - $timestart);
	echo ".\n";
}
else
	exit("This script removes all releases and nzb files from a poster or by searchname, name, groupname, guid or newer than x adddate/postdate.\nIf you are sure you want to run it, type php delete_releases.php [ fromname, searchname, name, groupname, guid, adddate/postdate ] equals [ name, guid, days(number) ]\nYou can also use like instead of = by doing type php delete_releases.php [ fromname, searchname, name, groupname, guid ] like [ name/guid ]\n");
