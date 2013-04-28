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
	function deleteReleases($sql, $type)
	{
		$releases = new Releases();
		$delcount = 0;
		foreach ($sql as $rel)
		{
			echo "Deleting, ".$type.": ".$rel['searchname']."\n";
			$releases->delete($rel['ID']);
			$delcount++;
		}
		return $delcount;
	}

	// 15 or more letters or numbers, nothing else.
	function deleteGibberish()
	{
		$type = "Gibberish";
		$db = new Db;
		$sql = $db->query("select ID, searchname from releases where searchname REGEXP '^[a-zA-Z0-9]{15,}$'");
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// 25 or more letters/numbers, probably hashed.
	function deleteHashed()
	{
		$type = "Hashed";
		$db = new Db;
		$sql = $db->query("select ID, searchname from releases where searchname REGEXP '[a-zA-Z0-9]{25,}'");
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// 5 or less letters/numbers.
	function deleteShort()
	{
		$type = "Short";
		$db = new Db;
		$sql = $db->query("select ID, searchname from releases where searchname REGEXP '^[a-zA-Z0-9]{0,5}$'");
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// Anything smaller than 30MB with an exe not in other misc or pc apps.
	function deleteExe()
	{
		$type = "EXE";
		$db = new Db;
		$sql = $db->query('select r.ID, r.searchname from releases r left join releasefiles rf on rf.releaseID = r.ID where rf.name like "%.exe%" and r.size < 30000000 and r.categoryID not in (4010, 4020, 4030, 4040, 4050, 4060, 4070, 7010)');
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}

	$totalDeleted = 0;

	$gibberishDeleted = deleteGibberish();
	$hashedDeleted = deleteHashed();
	$shortDeleted = deleteShort();
	$exeDeleted = deleteExe();

	$totalDeleted = $totalDeleted+$gibberishDeleted+$hashedDeleted+$shortDeleted+$exeDeleted;

	if ($totalDeleted > 0)
	{
		echo "Total Removed: ".$totalDeleted."\n";
		if($gibberishDeleted > 0)
			echo "Gibberish    : ".$gibberishDeleted."\n";
		if($hashedDeleted > 0)
			echo "Hashed       : ".$hashedDeleted."\n";
		if($shortDeleted > 0)
			echo "Short        : ".$shortDeleted."\n\n";
		if($exeDeleted > 0)
			echo "<30MB with .exe not in apps or misc: ".$exeDeleted."\n";
	}
	else
		exit("Nothing was found to delete.\n");
}
else
	exit("Run fixReleaseNames.php first. If you are sure you want to run this script, type php removeCrapReleases.php true\n");

?>
