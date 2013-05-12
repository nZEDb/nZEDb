<?php

/*
 * This script resets the relnamestatus to 1 on every release that has relnamestatus 2, so you can rerun fixReleaseNames.php it also recreates the searchname using namecleaner.php (like if you just made the release)
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/namecleaning.php");

if (isset($argv[1]) && $argv[1] == "true")
{
	$db = new DB;
	$res = $db->queryDirect("SELECT ID, name FROM releases");
	
	if (sizeof($res) > 0)
	{
		echo "Going to recreate all search names and reset relnamestatus, this can take a while.\n";
		$done = 0;
		$timestart = TIME();
		while ($row = mysqli_fetch_assoc($res))
		{
			$nc = new nameCleaning();
			$newname = $nc-> releaseCleaner($row['name']);
			$db->query(sprintf("UPDATE releases SET searchname = %s, relnamestatus = %d where ID = %d", $db->escapeString($newname), 1, $row['ID']));
			$done++;
			if ($done % 100 == 0)
				echo ".";
			if ($done % 10000 == 0)
				echo "\n";
		}
		$time = TIME() - $timestart;
		exit("\n".$done." releases changed in ".$time." seconds.\n");
	}
	else
		exit("You have no releases in the DB.\n");	
}
else
	exit("This scripts runs the subject names through name cleaner to create a clean search name, it also resets relnamestatus.\nType php ResetSearchname&resetRelnameStatus.php true\n");
?>
