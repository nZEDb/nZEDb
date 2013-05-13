<?php

/*
 * This script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.
 * Type php resetSearchname.php to see detailed info.
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/namecleaning.php");
require_once(FS_ROOT."/../../../www/lib/namefixer.php");

if (isset($argv[1]) && $argv[1] == "full")
{
	$db = new DB;
	$res = $db->queryDirect("SELECT ID, name FROM releases");
	
	if (sizeof($res) > 0)
	{
		echo "Going to recreate all search names, recategorize them and fix the names with namfixer, this can take a while.\n";
		$done = 0;
		$timestart = TIME();
		while ($row = mysqli_fetch_assoc($res))
		{
			$nc = new nameCleaning();
			$newname = $nc-> releaseCleaner($row['name']);
			$db->query(sprintf("UPDATE releases SET searchname = %s where ID = %d", $db->escapeString($newname), $row['ID']));
			$done++;
			if ($done % 100 == 0)
				echo ".";
			if ($done % 10000 == 0)
				echo "\n";
		}
		$timenc = TIME() - $timestart;
		echo "\n".$done." releases renamed in ".$timenc." seconds.\nNow the releases will be recategorized.\n";
		
		$releases = new Releases();
		$releases->resetCategorize();
		$categorized = $releases->categorizeRelease("name", "", true);
		$timecat = TIME() - $timestart;
		echo "\nFinished categorizing ".$categorized." releases in ".$timecat." seconds.\nFinally, the releases will be fixed using the NFO/filenames.\n";
		
		$namefixer = new Namefixer();
		$namefixer->fixNamesWithNfo(2,1,1,1);
		$namefixer->fixNamesWithFiles(2,1,1,1);
		$timetotal = TIME() - $timestart;
		echo "\nFinished recreating search names / recategorizing / refixing names in ".$timetotal." seconds.\n";
	}
	else
		exit("You have no releases in the DB.\n");	
}
else if (isset($argv[1]) && $argv[1] == "limited")
{
	$db = new DB;
	$res = $db->queryDirect("SELECT ID, name FROM releases where relnamestatus != 2");
	
	if (sizeof($res) > 0)
	{
		echo "Going to recreate search names that have not been fixed with namefixer, recategorize them, and fix them with namefixer, this can take a while.\n";
		$done = 0;
		$timestart = TIME();
		while ($row = mysqli_fetch_assoc($res))
		{
			$nc = new nameCleaning();
			$newname = $nc-> releaseCleaner($row['name']);
			$db->query(sprintf("UPDATE releases SET searchname = %s where ID = %d", $db->escapeString($newname), $row['ID']));
			$done++;
			if ($done % 100 == 0)
				echo ".";
			if ($done % 10000 == 0)
				echo "\n";
		}
		$timenc = TIME() - $timestart;
		echo "\n".$done." releases renamed in ".$timenc." seconds.\nNow the releases will be recategorized.\n";
		
		$releases = new Releases();
		$releases->resetCategorize("WHERE relnamestatus != 2");
		$categorized = $releases->categorizeRelease("name", "WHERE relnamestatus != 2", true);
		$timecat = TIME() - $timestart;
		echo "\nFinished categorizing ".$categorized." releases in ".$timecat." seconds.\nFinally, the releases will be fixed using the NFO/filenames.\n";
		
		$namefixer = new Namefixer();
		$namefixer->fixNamesWithNfo(2,1,1,1);
		$namefixer->fixNamesWithFiles(2,1,1,1);
		$timetotal = TIME() - $timestart;
		echo "\nFinished recreating search names / recategorizing / refixing names in ".$timetotal." seconds.\n";
	}
	else
		exit("You have no releases in the DB.\n");	
}
else
	exit("This script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.\nType php ResetSearchname&resetRelnameStatus.php full to run this, recategorize and refix release names on all releases.\nType php ResetSearchname&resetRelnameStatus.php limited to run this on releases that have not had their names fixed, then categorizing them.\n");
?>
