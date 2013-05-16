<?php

/*
 * This script deletes releases that match certain criteria, type php removeCrapReleases.php false for details.
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");
require_once(FS_ROOT."/../../../www/lib/site.php");

if (!isset($argv[1]) && !isset($argv[2]))
{
	exit("Run fixReleaseNames.php first to attempt to fix release names.\n"
		."To see an explanation of what this script does, type php removeCrapReleases.php false\n"
		."If you are sure you want to run this script, type php removeCrapReleases.php true full\n"
		."The second mandatory argument is the time in hours(ex: 12) to go back, or you can type full.\n"
		."You can pass 1 optional third argument:\n"
		."gibberish | hashed | short | executable | passwordurl | passworded | size | sample\n");
}
else if (isset($argv[1]) && $argv[1] == "false" && !isset($argv[2]))
{
	exit("gibberish deletes releases where the name is only letters or numbers and is 15 characters or more.\n"
		."hashed deletes releases where the name contains a string of 25 or more numbers or letters.\n"
		."short deletes releases where the name is only numbers or letters and is 5 characters or less\n"
		."executable deletes releases not in other misc or the apps sections and contains an .exe or install.bin file\n"
		."passwordurl deletes releases which contain a password.url file\n"
		."passworded deletes releases which contain password or passworded in the search name\n"
		."size deletes releases smaller than 1MB and has only 1 file not in mp3/books\n"
		."sample deletes releases smaller than 40MB and has more than 1 file and has sample in the name\n"
		."php removeCrapReleases.php true full runs all the above\n"
		."php removeCrapReleases.php true full gibberish runs only this type\n");
}

if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "full")
{
	echo "Removing crap releases - no time limit.\n";
	$and = "";
}
else if (isset($argv[1]) && isset($argv[2]) && is_numeric($argv[2]))
{
	echo "Removing crap releases from the past ".$argv[2]." hour(s).\n";
	$and = " and adddate > (now() - interval ".$argv[2]." hour) order by ID asc";
}
else if (!isset($argv[2]) || $argv[2] !== "full" || !is_numeric($argv[2]))
	exit("ERROR: Wrong second argument.\n");

if (isset($argv[1]) && $argv[1] == "true")
{
	function deleteReleases($sql, $type)
	{
		$releases = new Releases();
		$s = new Sites();
		$site = $s->get();
		
		$delcount = 0;
		foreach ($sql as $rel)
		{
			echo "Deleting, ".$type.": ".$rel['searchname']."\n";
			$releases->fastDelete($rel['ID'], $rel['guid'], $site);
			$delcount++;
		}
		return $delcount;
	}

	// 15 or more letters or numbers, nothing else.
	function deleteGibberish($and)
	{
		$type = "Gibberish";
		$db = new Db;
		$sql = $db->query("select ID, guid, searchname from releases where searchname REGEXP '^[a-zA-Z0-9]{15,}$' and nfostatus = 0 and relnamestatus = 2 and rarinnerfilecount = 0".$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// 25 or more letters/numbers, probably hashed.
	function deleteHashed($and)
	{
		$type = "Hashed";
		$db = new Db;
		$sql = $db->query("select ID, guid, searchname from releases where searchname REGEXP '[a-zA-Z0-9]{25,}' and nfostatus = 0 and relnamestatus = 2 and rarinnerfilecount = 0".$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// 5 or less letters/numbers.
	function deleteShort($and)
	{
		$type = "Short";
		$db = new Db;
		$sql = $db->query("select ID, guid, searchname from releases where searchname REGEXP '^[a-zA-Z0-9]{0,5}$' and nfostatus = 0 and relnamestatus = 2 and rarinnerfilecount = 0".$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// Anything with an exe or install.bin not in other misc or pc apps.
	function deleteExecutable($and)
	{
		$type = "Executable";
		$db = new Db;
		$sql = $db->query('select r.ID, r.guid, r.searchname from releases r left join releasefiles rf on rf.releaseID = r.ID where rf.name like "%.exe%" and r.size < 30000000 and r.categoryID not in (4010, 4020, 4030, 4040, 4050, 4060, 4070, 7010)'.$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// Anything with a password.url file.
	function deletePasswordURL($and)
	{
		$type = "PasswordURL";
		$db = new Db;
		$sql = $db->query('select r.ID, r.guid, r.searchname from releases r left join releasefiles rf on rf.releaseID = r.ID where rf.name like "%password.url%"'.$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// Password in the searchname
	function deletePassworded($and)
	{
		$type = "Passworded";
		$db = new Db;
		$sql = $db->query("select ID, guid, searchname from releases where searchname REGEXP 'Passworded|Password Protect' and nzbstatus = 1".$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// Anything that is 1 part and smaller than 1MB and not in MP3/books.
	function deleteSize($and)
	{
		$type = "Size";
		$db = new Db;
		$sql = $db->query('select ID, guid, searchname from releases where totalPart = 1 and size < 1000000 and categoryID not in (8010, 8020, 8030, 8050)'.$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}
	
	// More than 1 part, less than 40MB, sample in name. TV/Movie sections.
	function deleteSample($and)
	{
		$type = "Sample";
		$db = new Db;
		$sql = $db->query('select ID, guid, searchname from releases where totalPart > 1 and name like "%sample%" and size < 40000000 and categoryID in (5010, 5020, 5030, 5040, 5050, 5060, 5070, 5080, 2010, 2020, 2030, 2040, 2050, 2060)'.$and);
		$delcount = deleteReleases($sql, $type);
		return $delcount;
	}

	$totalDeleted = 0;
	$gibberishDeleted = 0;
	$hashedDeleted = 0;
	$shortDeleted = 0;
	$executableDeleted = 0;
	$PURLDeleted = 0;
	$PassDeleted = 0;
	$sizeDeleted = 0;
	$sampleDeleted = 0;
	
	if (isset($argv[3]))
	{
		if (isset($argv[3]) && $argv[3] == "gibberish")
			$gibberishDeleted = deleteGibberish($and);
		if (isset($argv[3]) && $argv[3] == "hashed")
			$hashedDeleted = deleteHashed($and);
		if (isset($argv[3]) && $argv[3] == "short")
			$shortDeleted = deleteShort($and);
		if (isset($argv[3]) && $argv[3] == "executable")
			$executableDeleted = deleteExecutable($and);
		if (isset($argv[3]) && $argv[3] == "passwordurl")
			$PURLDeleted = deletePasswordURL($and);
		if (isset($argv[3]) && $argv[3] == "passworded")
			$PURLDeleted = deletePassworded($and);
		if (isset($argv[3]) && $argv[3] == "size")
			$sizeDeleted = deleteSize($and);
		if (isset($argv[3]) && $argv[3] == "sample")
			$sampleDeleted = deleteSample($and);
	}
	else
	{
		$gibberishDeleted = deleteGibberish($and);
		$hashedDeleted = deleteHashed($and);
		$shortDeleted = deleteShort($and);
		$executableDeleted = deleteExecutable($and);
		$PURLDeleted = deletePasswordURL($and);
		$PassDeleted = deletePassworded($and);
		$sizeDeleted = deleteSize($and);
		$sampleDeleted = deleteSample($and);
	}

	$totalDeleted = $totalDeleted+$gibberishDeleted+$hashedDeleted+$shortDeleted+$executableDeleted+$PURLDeleted+$PassDeleted+$sizeDeleted+$sampleDeleted;
	
	if ($totalDeleted > 0)
	{
		echo "Total Removed: ".$totalDeleted."\n";
		if($gibberishDeleted > 0)
			echo "Gibberish    : ".$gibberishDeleted."\n";
		if($hashedDeleted > 0)
			echo "Hashed       : ".$hashedDeleted."\n";
		if($shortDeleted > 0)
			echo "Short        : ".$shortDeleted."\n";
		if($executableDeleted > 0)
			echo "Executable   : ".$executableDeleted."\n";
		if($PURLDeleted > 0)
			echo "PURL         : ".$PURLDeleted."\n";
		if($PassDeleted > 0)
			echo "Passwordeded : ".$PassDeleted."\n";
		if($sizeDeleted > 0)
			echo "Size         : ".$sizeDeleted."\n";
		if($sampleDeleted > 0)
			echo "Sample       : ".$sampleDeleted."\n";
	}
	else
		exit("Nothing was found to delete.\n");
}
?>
