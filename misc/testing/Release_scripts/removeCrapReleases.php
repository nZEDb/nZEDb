<?php

/*
 * This script deletes releases that match certain criteria, type php removeCrapReleases.php true
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");

if (isset($argv[1]) && $argv[1] == true)
{
	function deleteReleases($sql)
	{
		$releases = new Releases();
		$delcount = 0;
		foreach ($sql as $rel)
		{
			$releases->delete($rel['ID']);
			$delcount++;
		}
		return $delcount;
	}

	// 15 or more letters, nothing else.
	function deleteLettersOnly()
	{
		$db = new Db;
		$sql = $db->query("select ID from releases where searchname REGEXP '^[a-zA-Z]{15,}$'");
		$delcount = deleteReleases($sql);
		return $delcount;
	}
	
	// 25 or more letters/numbers, probably hashed.
	function deleteHashed()
	{
		$db = new Db;
		$sql = $db->query("select ID from releases where searchname REGEXP '[a-zA-Z0-9]{25,}'");
		$delcount = deleteReleases($sql);
		return $delcount;
	}

	$totalDeleted = 0;

	$lettersOnlyDeleted = deleteLettersOnly();
	$hashedDeleted = deleteHashed();

	$totalDeleted = $totalDeleted+$lettersOnlyDeleted+$hashedDeleted;

	if ($totalDeleted > 0)
	{
		echo "Total Removed: ".$totalDeleted."\n";
		if($lettersOnlyDeleted > 0)
			echo "Letters only : ".$lettersOnlyDeleted."\n";
		if($hashedDeleted > 0)
			echo "Hashed       : ".$hashedDeleted."\n";
	}
	else
		exit("Nothing was found to delete.\n");
}
else
	exit("If you are sure you want to run this script, type php removeCrapReleases.php true\n");

?>
