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
			echo "Deleting: ".$rel['searchname']."\n";
			$releases->delete($rel['ID']);
			$delcount++;
		}
		return $delcount;
	}

	// 15 or more letters or numbers, nothing else.
	function deleteGibberish()
	{
		$db = new Db;
		$sql = $db->query("select ID, searchname from releases where searchname REGEXP '^[a-zA-Z0-9]{15,}$'");
		$delcount = deleteReleases($sql);
		return $delcount;
	}
	
	// 25 or more letters/numbers, probably hashed.
	function deleteHashed()
	{
		$db = new Db;
		$sql = $db->query("select ID, searchname from releases where searchname REGEXP '[a-zA-Z0-9]{25,}'");
		$delcount = deleteReleases($sql);
		return $delcount;
	}
	
	// 5 or less letters/numbers.
	function deleteShort()
	{
		$db = new Db;
		$sql = $db->query("select ID, searchname from releases where searchname REGEXP '^[a-zA-Z0-9]{0,5}$'");
		$delcount = deleteReleases($sql);
		return $delcount;
	}

	$totalDeleted = 0;

	$gibberishDeleted = deleteGibberish();
	$hashedDeleted = deleteHashed();
	$shortDeleted = deleteShort();

	$totalDeleted = $totalDeleted+$gibberishDeleted+$hashedDeleted+$shortDeleted;

	if ($totalDeleted > 0)
	{
		echo "Total Removed: ".$totalDeleted."\n";
		if($gibberishDeleted > 0)
			echo "Gibberish    : ".$gibberishDeleted."\n";
		if($hashedDeleted > 0)
			echo "Hashed       : ".$hashedDeleted."\n";
		if($shortDeleted > 0)
			echo "Short        : ".$shortDeleted."\n";
	}
	else
		exit("Nothing was found to delete.\n");
}
else
	exit("Run fixReleaseNames.php first. If you are sure you want to run this script, type php removeCrapReleases.php true\n");

?>
