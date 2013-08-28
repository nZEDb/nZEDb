<?php
/* This script deletes releases that match certain criteria, type php removeCrapReleases.php false for details. */

if (!isset($argv[1]) && !isset($argv[2]))
{
	exit("Run fixReleaseNames.php first to attempt to fix release names. This will miss some releases if you have not set fixReleaseNames to set the release as checked.\n"
		."To see an explanation of what this script does, type php removeCrapReleases.php false\n"
		."If you are sure you want to run this script, type php removeCrapReleases.php true full\n"
		."The second mandatory argument is the time in hours(ex: 12) to go back, or you can type full.\n"
		."You can pass 1 optional third argument:\n"
		."blacklist | executable | gibberish | hashed | installbin | passworded | passwordurl | sample | scr | short | size\n");
}
else if (isset($argv[1]) && $argv[1] == "false" && !isset($argv[2]))
{
	exit("blacklist deletes releases after applying the configured blacklist regexes.\n"
		."executable deletes releases not in other misc or the apps sections and contain an .exe file\n"
		."gibberish deletes releases where the name is only letters or numbers and is 15 characters or more.\n"
		."hashed deletes releases where the name contains a string of 25 or more numbers or letters.\n"
		."installbin deletes releases which contain an install.bin file\n"
		."passworded deletes releases which contain password or passworded in the search name\n"
		."passwordurl deletes releases which contain a password.url file\n"
		."sample deletes releases smaller than 40MB and has more than 1 file and has sample in the name\n"
		."scr deletes releases where .scr extension is found in the files or subject\n"
		."short deletes releases where the name is only numbers or letters and is 5 characters or less\n"
		."size deletes releases smaller than 1MB and has only 1 file not in mp3/books\n"
		."php removeCrapReleases.php true full runs all the above\n"
		."php removeCrapReleases.php true full gibberish runs only this type\n");
}

if (isset($argv[1]) && !is_numeric($argv[1]) && isset($argv[2]) && $argv[2] == "full")
{
	echo "Removing crap releases - no time limit.\n";
	$and = "";
}
else if (isset($argv[1]) && isset($argv[2]) && is_numeric($argv[2]))
{
	echo "Removing crap releases from the past ".$argv[2]." hour(s).\n";
	$and = " AND adddate > (NOW() - INTERVAL ".$argv[2]." HOUR) ORDER BY id ASC";
}
else if (!isset($argv[2]) || $argv[2] !== "full" || !is_numeric($argv[2]))
	exit("ERROR: Wrong second argument.\n");

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");
require_once(FS_ROOT."/../../../www/lib/site.php");

$delete = 0;
if (isset($argv[1]) && $argv[1] == "true")
{
	$delete = 1;

	function deleteReleases($sql, $type)
	{
		global $delete;
		$releases = new Releases();
		$s = new Sites();
		$site = $s->get();

		$delcount = 0;
		foreach ($sql as $rel)
		{
			if ($delete == 1)
			{
				echo "Deleting: ".$type.": ".$rel['searchname']."\n";
				$releases->fastDelete($rel['id'], $rel['guid'], $site);
			}
			else
				echo "Would be deleting: ".$type.": ".$rel['searchname']."\n";
			$delcount++;
		}
		return $delcount;
	}

	// 15 or more letters or numbers, nothing else.
	function deleteGibberish($and)
	{
		$db = new DB();
		if ($db->dbSystem() == "mysql")
			$regex = "searchname REGEXP '^[a-zA-Z0-9]{15,}$'";
		else if ($db->dbSystem() == "pgsql")
			$regex = "regexp_matches(searchname, '^[a-zA-Z0-9]{15,}$')";

		$sql = $db->query("SELECT id, guid, searchname FROM releases WHERE {$regex} AND nfostatus = 0 AND relnamestatus > 1 AND rarinnerfilecount = 0".$and);
		$delcount = deleteReleases($sql, "Gibberish");
		return $delcount;
	}

	// 25 or more letters/numbers, probably hashed.
	function deleteHashed($and)
	{
		$db = new DB();
		if ($db->dbSystem() == "mysql")
			$regex = "searchname REGEXP '[a-zA-Z0-9]{25,}'";
		else if ($db->dbSystem() == "pgsql")
			$regex = "regexp_matches(searchname, '[a-zA-Z0-9]{25,}')";

		$sql = $db->query("SELECT id, guid, searchname FROM releases WHERE {$regex} AND nfostatus = 0 AND relnamestatus > 1 AND rarinnerfilecount = 0".$and);
		$delcount = deleteReleases($sql, "Hashed";);
		return $delcount;
	}

	// 5 or less letters/numbers.
	function deleteShort($and)
	{
		$db = new DB();
		if ($db->dbSystem() == "mysql")
			$regex = "searchname REGEXP '^[a-zA-Z0-9]{0,5}$'";
		else if ($db->dbSystem() == "pgsql")
			$regex = "regexp_matches(searchname, '^[a-zA-Z0-9]{0,5}$')";

		$sql = $db->query("SELECT id, guid, searchname FROM releases WHERE {$regex} AND nfostatus = 0 AND relnamestatus > 1 AND rarinnerfilecount = 0".$and);
		$delcount = deleteReleases($sql, "Short";);
		return $delcount;
	}

	// Anything with an exe not in other misc or pc apps/games.
	function deleteExecutable($and)
	{
		$db = new DB();
		$sql = $db->query('SELECT r.id, r.guid, r.searchname FROM releases r INNER JOIN releasefiles rf ON rf.releaseid = r.id WHERE rf.name LIKE "%.exe%" AND r.categoryid NOT IN (4000, 4010, 4020, 4050, 7010)'.$and);
		$delcount = deleteReleases($sql, "Executable");
		return $delcount;
	}

	// Anything with an install.bin file.
	function deleteInstallBin($and)
	{
		$db = new DB();
		$sql = $db->query('SELECT r.id, r.guid, r.searchname FROM releases r INNER JOIN releasefiles rf ON rf.releaseid = r.id WHERE rf.name LIKE "%install.bin%"'.$and);
		$delcount = deleteReleases($sql, "install.bin");
		return $delcount;
	}

	// Anything with a password.url file.
	function deletePasswordURL($and)
	{
		$db = new DB();
		$sql = $db->query('SELECT r.id, r.guid, r.searchname FROM releases r INNER JOIN releasefiles rf ON rf.releaseid = r.id WHERE rf.name LIKE "%password.url%"'.$and);
		$delcount = deleteReleases($sql, "PasswordURL");
		return $delcount;
	}

	// Password in the searchname
	function deletePassworded($and)
	{
		$db = new DB();
		$sql = $db->query("SELECT id, guid, searchname FROM releases WHERE ( searchname LIKE '%passworded%' OR searchname LIKE '%password protect%' OR searchname LIKE '%password%' OR searchname LIKE '%passwort%' ) AND searchname NOT LIKE '%no password%' AND searchname NOT LIKE '%not passworded%' AND searchname NOT LIKE '%unlocker%' AND searchname NOT LIKE '%reset%' AND searchname NOT LIKE '%recovery%' AND searchname NOT LIKE '%keygen%' and searchname NOT LIKE '%advanced%' AND nzbstatus IN (1, 2) AND categoryid NOT IN (4000, 4010, 4020, 4030, 4040, 4050, 4060, 4070, 7000, 7010)".$and);
		$delcount = deleteReleases($sql, "Passworded");
		return $delcount;
	}

	// Anything that is 1 part and smaller than 1MB and not in MP3/books.
	function deleteSize($and)
	{
		$db = new DB();
		$sql = $db->query("SELECT id, guid, searchname FROM releases WHERE totalpart = 1 AND size < 1000000 AND categoryid NOT IN (8000, 8010, 8020, 8030, 8040, 8050, 8060, 3010)".$and);
		$delcount = deleteReleases($sql, "Size");
		return $delcount;
	}

	// More than 1 part, less than 40MB, sample in name. TV/Movie sections.
	function deleteSample($and)
	{
		$db = new DB();
		$sql = $db->query('SELECT id, guid, searchname FROM releases WHERE totalpart > 1 AND name LIKE "%sample%" AND size < 40000000 AND categoryid IN (5010, 5020, 5030, 5040, 5050, 5060, 5070, 5080, 2010, 2020, 2030, 2040, 2050, 2060)'.$and);
		$delcount = deleteReleases($sql, "Sample");
		return $delcount;
	}

	// Anything with a scr file in the filename/subject.
	function deleteScr($and)
	{
		$db = new DB();
		if ($db->dbSystem() == "mysql")
			$regex = "(rf.name REGEXP '\.scr$' OR r.name REGEXP '\.scr($| |\")')";
		else if ($db->dbSystem() == "pgsql")
			$regex = "(regexp_matches(rf.name, '\.scr$') OR regexp_matches(r.name, '\.scr($| |\")'))";

		$sql = $db->query("SELECT r.id, r.guid, r.searchname FROM releases r LEFT JOIN releasefiles rf ON rf.releaseid = r.id WHERE {$regex}".$and);
		$delcount = deleteReleases($sql, ".scr");
		return $delcount;
	}

	// Use the site blacklists to delete releases.
	function deleteBlacklist($and)
	{
		$db = new DB();
		$regexes = $db->query('SELECT regex FROM binaryblacklist WHERE status = 1');
		$delcount = 0;
		if(sizeof($regexes > 0))
		{
			foreach ($regexes as $regex)
			{
				if ($db->dbSystem() == "mysql")
					$regex = "(rf.name REGEXP ".$db->escapeString($regex["regex"])." OR r.name REGEXP ".$db->escapeString($regex["regex"]).")";
				else if ($db->dbSystem() == "pgsql")
					$regex = "(regexp_matches(rf.name, ".$db->escapeString($regex["regex"]).") OR regexp_matches(r.name, ".$db->escapeString($regex["regex"])."))";
				$sql = $db->query("SELECT r.id, r.guid, r.searchname FROM releases r LEFT JOIN releasefiles rf ON rf.releaseid = r.id WHERE (rf.name REGEXP".$db->escapeString($regex["regex"])." OR r.name REGEXP".$db->escapeString($regex["regex"]).")".$and);
				$delcount += deleteReleases($sql, "Blacklist");
			}
		}
		return $delcount;
	}

	$totalDeleted = $gibberishDeleted = $hashedDeleted = $shortDeleted = $executableDeleted = $installBinDeleted = $PURLDeleted = $PassDeleted = $sizeDeleted = $sampleDeleted = $scrDeleted = $blacklistDeleted = 0;

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
		if (isset($argv[3]) && $argv[3] == "installbin")
			$installBinDeleted = deleteInstallBin($and);
		if (isset($argv[3]) && $argv[3] == "passwordurl")
			$PURLDeleted = deletePasswordURL($and);
		if (isset($argv[3]) && $argv[3] == "passworded")
			$PURLDeleted = deletePassworded($and);
		if (isset($argv[3]) && $argv[3] == "size")
			$sizeDeleted = deleteSize($and);
		if (isset($argv[3]) && $argv[3] == "sample")
			$sampleDeleted = deleteSample($and);
		if (isset($argv[3]) && $argv[3] == "scr")
			$scrDeleted = deleteScr($and);
		if (isset($argv[3]) && $argv[3] == "blacklist")
			$blacklistDeleted = deleteBlacklist($and);
	}
	else
	{
		$gibberishDeleted = deleteGibberish($and);
		$hashedDeleted = deleteHashed($and);
		$shortDeleted = deleteShort($and);
		$executableDeleted = deleteExecutable($and);
		$installBinDeleted = deleteInstallBin($and);
		$PURLDeleted = deletePasswordURL($and);
		$PassDeleted = deletePassworded($and);
		$sizeDeleted = deleteSize($and);
		$sampleDeleted = deleteSample($and);
		$scrDeleted = deleteScr($and);
		$blacklistDeleted = deleteBlacklist($and);
	}

	$totalDeleted = $totalDeleted+$gibberishDeleted+$hashedDeleted+$shortDeleted+$executableDeleted+$installBinDeleted+$PURLDeleted+$PassDeleted+$sizeDeleted+$sampleDeleted+$scrDeleted+$blacklistDeleted;

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
		if($installBinDeleted > 0)
			echo "install.bin  : ".$installBinDeleted."\n";
		if($PURLDeleted > 0)
			echo "PURL         : ".$PURLDeleted."\n";
		if($PassDeleted > 0)
			echo "Passwordeded : ".$PassDeleted."\n";
		if($sizeDeleted > 0)
			echo "Size         : ".$sizeDeleted."\n";
		if($sampleDeleted > 0)
			echo "Sample       : ".$sampleDeleted."\n";
		if($scrDeleted > 0)
			echo ".scr         : ".$scrDeleted."\n";
		if($blacklistDeleted > 0)
			echo "Blacklist    : ".$blacklistDeleted."\n";
	}
	else
		exit("Nothing was found to delete.\n");
}
?>
