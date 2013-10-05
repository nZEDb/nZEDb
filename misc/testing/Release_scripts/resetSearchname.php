<?php
/* This script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.
 * Type php resetSearchname.php to see detailed info. */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/namecleaning.php");
require_once(FS_ROOT."/../../../www/lib/namefixer.php");
require_once(FS_ROOT."/../../../www/lib/consoletools.php");

if (isset($argv[1]) && $argv[1] == "full")
{
	$db = new DB();
	$res = $db->query("SELECT releases.id, releases.name, groups.name AS gname FROM releases INNER JOIN groups ON releases.groupid = groups.id WHERE relnamestatus NOT IN (3, 7)");

	if (count($res) > 0)
	{
		echo "Going to recreate all search names, recategorize them and fix the names with namefixer, this can take a while.\n";
		$done = 0;
		$timestart = TIME();
		$consoletools = new consoleTools();
		foreach ($res as $row)
		{
			$nc = new nameCleaning();
			$newname = $nc->releaseCleaner($row['name'], $row['gname']);
			if (is_array($newname))
				$newname = $newname['cleansubject'];
			$db->queryExec(sprintf("UPDATE releases SET searchname = %s WHERE id = %d", $db->escapeString($newname), $row['id']));
			$done++;
			$consoletools->overWrite("Renaming:".$consoletools->percentString($done,count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo "\n".$done." releases renamed in ".$timenc.".\nNow the releases will be recategorized.\n";

		$releases = new Releases();
		$releases->resetCategorize();
		$categorized = $releases->categorizeRelease("name", "", true);
		$timecat = $consoletools->convertTime(TIME() - $timestart);
		echo "\nFinished categorizing ".$categorized." releases in ".$timecat.".\nFinally, the releases will be fixed using the NFO/filenames.\n";

		$namefixer = new Namefixer();
		$namefixer->fixNamesWithNfo(2,1,1,1);
		$namefixer->fixNamesWithFiles(2,1,1,1);
		$timetotal = $consoletools->convertTime(TIME() - $timestart);
		echo "\nFinished recreating search names / recategorizing / refixing names in ".$timetotal.".\n";
	}
	else
		exit("You have no releases in the DB.\n");
}
else if (isset($argv[1]) && $argv[1] == "limited")
{
	$db = new DB();
	$res = $db->query("SELECT releases.id, releases.name, groups.name AS gname FROM releases INNER JOIN groups ON releases.groupid = groups.id WHERE relnamestatus IN (0, 1, 20, 21, 22)");

	if (count($res) > 0)
	{
		echo "Going to recreate search names that have not been fixed with namefixer, recategorize them, and fix them with namefixer, this can take a while.\n";
		$done = 0;
		$timestart = TIME();
		$consoletools = new consoleTools();
		foreach ($res as $row)
		{
			$nc = new nameCleaning();
			$newname = $nc->releaseCleaner($row['name'], $row['gname']);
			if (is_array($newname))
				$newname = $newname['cleansubject'];
			$db->queryExec(sprintf("UPDATE releases SET searchname = %s WHERE id = %d", $db->escapeString($newname), $row['id']));
			$done++;
			$consoletools->overWrite("Renaming:".$consoletools->percentString($done,count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo "\n".$done." releases renamed in ".$timenc.".\nNow the releases will be recategorized.\n";

		$releases = new Releases();
		$releases->resetCategorize("WHERE relnamestatus IN (0, 1, 20, 21, 22)");
		$categorized = $releases->categorizeRelease("name", "WHERE relnamestatus IN (0, 1, 20, 21, 22)", true);
		$timecat = $consoletools->convertTime(TIME() - $timestart);
		echo "\nFinished categorizing ".$categorized." releases in ".$timecat.".\nFinally, the releases will be fixed using the NFO/filenames.\n";

		$namefixer = new Namefixer();
		$namefixer->fixNamesWithNfo(2,1,1,1);
		$namefixer->fixNamesWithFiles(2,1,1,1);
		$timetotal = $consoletools->convertTime(TIME() - $timestart);
		echo "\nFinished recreating search names / recategorizing / refixing names in ".$timetotal.".\n";
	}
	else
		exit("You have no releases in the DB.\n");
}
elseif (isset($argv[1]) && $argv[1] == "reset")
{
	$db = new DB();
	$res = $db->query("SELECT releases.id, releases.name, groups.name AS gname FROM releases INNER JOIN groups ON releases.groupid = groups.id WHERE relnamestatus NOT IN (3, 7)");

	if (count($res) > 0)
	{
		echo "Going to reset search names, this can take a while.\n";
		$done = 0;
		$timestart = TIME();
		$consoletools = new consoleTools();
		foreach ($res as $row)
		{
			$nc = new nameCleaning();
			$newname = $nc->releaseCleaner($row['name'], $row['gname']);
			if (is_array($newname))
				$newname = $newname['cleansubject'];
			$db->queryExec(sprintf("UPDATE releases SET searchname = %s where id = %d", $db->escapeString($newname), $row['id']));
			$done++;
			$consoletools->overWrite("Renaming:".$consoletools->percentString($done,count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo "\n".$done." releases renamed in ".$timenc.".\n";
	}
}
else
	exit("This script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.\nType php resetSearchname.php full to run this, recategorize and refix release names on all releases.\nType php resetSearchname.php limited to run this on releases that have not had their names fixed, then categorizing them.\nTo simply reset searchnames only type resetSearchname.php reset\n\n");
?>
