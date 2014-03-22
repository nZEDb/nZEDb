<?php

/* This script deletes releases that match certain criteria, type php removeCrapReleases.php false for details. */
require_once dirname(__FILE__) . '/../../../www/config.php';

$c = new ColorCLI();

if (!isset($argv[1]) && !isset($argv[2])) {
	exit($c->error("\nRun fixReleaseNames.php first to attempt to fix release names. This will miss some releases if you have not set fixReleaseNames to set the release as checked.\n\n"
			. "php $argv[0] false               ...:To see an explanation of what this script does.\n"
			. "php $argv[0] true full           ...:If you are sure you want to run this script.\n"
			. "\nThe second mandatory argument is the time in hours(ex: 12) to go back, or you can type full.\n"
			. "You can pass 1 optional third argument:\n"
			. "blacklist | executable | gibberish | hashed | installbin | passworded | passwordurl | sample | scr | short | size\n"));
} else if (isset($argv[1]) && $argv[1] == 'false' && !isset($argv[2])) {
	exit($c->primary("blacklist:   deletes releases after applying the configured blacklist regexes.\n"
			. "executable:  deletes releases not in other misc or the apps sections and contain an .exe file\n"
			. "gibberish:   deletes releases where the name is only letters or numbers and is 15 characters or more.\n"
			. "hashed:      deletes releases where the name contains a string of 25 or more numbers or letters.\n"
			. "installbin:  deletes releases which contain an install.bin file\n"
			. "passworded:  deletes releases which contain password or passworded in the search name\n"
			. "passwordurl: deletes releases which contain a password.url file\n"
			. "sample:      deletes releases smaller than 40MB and has more than 1 file and has sample in the name\n"
			. "scr:         deletes releases where .scr extension is found in the files or subject\n"
			. "short:       deletes releases where the name is only numbers or letters and is 5 characters or less\n"
			. "size:        deletes releases smaller than 1MB and has only 1 file not in mp3/books\n\n"
			. "php $argv[0] true full             ...: To run all the above\n"
			. "php $argv[0] true full gibberish   ...: To run only this type\n"));
}

if (isset($argv[1]) && !is_numeric($argv[1]) && isset($argv[2]) && $argv[2] == 'full') {
	echo $c->header("Removing crap releases - no time limit.");
	$and = '';
} else if (isset($argv[1]) && isset($argv[2]) && is_numeric($argv[2])) {
	echo $c->header('Removing crap releases from the past ' . $argv[2] . " hour(s).");
	$db = new DB();
	if ($db->dbSystem() == 'mysql') {
		$and = ' AND adddate > (NOW() - INTERVAL ' . $argv[2] . ' HOUR) ORDER BY id ASC';
	} else {
		$and = " AND adddate > (NOW() - INTERVAL '" . $argv[2] . " HOURS') ORDER BY id ASC";
	}
} else if (!isset($argv[2]) || $argv[2] !== 'full' || !is_numeric($argv[2])) {
	exit($c->error("\nERROR: Wrong second argument.\n"));
}

$delete = 0;
if (isset($argv[1]) && $argv[1] == 'true') {
	$delete = 1;
}

function deleteReleases($sql, $type)
{
	global $delete;
	$releases = new Releases();
	$c = new ColorCLI();
	$delcount = 0;
	foreach ($sql as $rel) {
		if ($delete == 1) {
			echo $c->primary('Deleting: ' . $type . ': ' . $rel['searchname']);
			$releases->fastDelete($rel['id'], $rel['guid']);
		} else {
			echo $c->primary('Would be deleting: ' . $type . ': ' . $rel['searchname']);
		}
		$delcount++;
	}
	return $delcount;
}

// 15 or more letters or numbers, nothing else.
function deleteGibberish($and)
{
	$db = new DB();
	if ($db->dbSystem() == 'mysql') {
		$regex = "searchname REGEXP '^[a-zA-Z0-9]{15,}$'";
	} else {
		$regex = "searchname ~ '^[a-zA-Z0-9]{15,}$'";
	}

	$sql = $db->prepare("SELECT id, guid, searchname FROM releases WHERE {$regex} AND nfostatus = 0 AND iscategorized = 1 AND rarinnerfilecount = 0" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Gibberish');
	return $delcount;
}

// 25 or more letters/numbers, probably hashed.
function deleteHashed($and)
{
	$db = new DB();
	if ($db->dbSystem() == 'mysql') {
		$regex = "searchname REGEXP '[a-zA-Z0-9]{25,}'";
	} else {
		$regex = "searchname ~ '[a-zA-Z0-9]{25,}'";
	}

	$sql = $db->prepare("SELECT id, guid, searchname FROM releases WHERE {$regex} AND nfostatus = 0 AND iscategorized = 1 AND rarinnerfilecount = 0" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Hashed');
	return $delcount;
}

// 5 or less letters/numbers.
function deleteShort($and)
{
	$db = new DB();
	if ($db->dbSystem() == 'mysql') {
		$regex = "searchname REGEXP '^[a-zA-Z0-9]{0,5}$'";
	} else {
		$regex = "searchname ~ '^[a-zA-Z0-9]{0,5}$'";
	}

	$sql = $db->prepare("SELECT id, guid, searchname FROM releases WHERE {$regex} AND nfostatus = 0 AND iscategorized = 1 AND rarinnerfilecount = 0" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Short');
	return $delcount;
}

// Anything with an exe not in other misc or pc apps/games.
function deleteExecutable($and)
{
	$db = new DB();
	$like = 'ILIKE';
	if ($db->dbSystem() == 'mysql') {
		$like = 'LIKE';
	}
	$sql = $db->prepare("SELECT r.id, r.guid, r.searchname FROM releases r INNER JOIN releasefiles rf ON rf.releaseid = r.id WHERE r.searchname NOT " . $like . " '%.exes%' AND rf.name " . $like . " '%.exe%' AND r.categoryid NOT IN (4000, 4010, 4020, 4050, 7010)" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Executable');
	return $delcount;
}

// Anything with an install.bin file.
function deleteInstallBin($and)
{
	$db = new DB();
	$like = 'ILIKE';
	if ($db->dbSystem() == 'mysql') {
		$like = 'LIKE';
	}
	$sql = $db->prepare("SELECT r.id, r.guid, r.searchname FROM releases r INNER JOIN releasefiles rf ON rf.releaseid = r.id WHERE rf.name " . $like . " '%install.bin%'" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'install.bin');
	return $delcount;
}

// Anything with a password.url file.
function deletePasswordURL($and)
{
	$db = new DB();
	$like = 'ILIKE';
	if ($db->dbSystem() == 'mysql') {
		$like = 'LIKE';
	}
	$sql = $db->prepare("SELECT r.id, r.guid, r.searchname FROM releases r INNER JOIN releasefiles rf ON rf.releaseid = r.id WHERE rf.name " . $like . " '%password.url%'" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'PasswordURL');
	return $delcount;
}

// Password in the searchname
function deletePassworded($and)
{
	$db = new DB();
	$like = 'ILIKE';
	if ($db->dbSystem() == 'mysql') {
		$like = 'LIKE';
	}
	$sql = $db->prepare("SELECT id, guid, searchname FROM releases WHERE ( searchname " . $like . " '%passworded%' OR searchname " . $like . " '%password protect%' OR searchname " . $like . " '%password%' OR searchname " . $like . " '%passwort%' ) AND searchname NOT " . $like . " '%no password%' AND searchname NOT " . $like . " '%not passworded%' AND searchname NOT " . $like . " '%unlocker%' AND searchname NOT " . $like . " '%reset%' AND searchname NOT " . $like . " '%recovery%' AND searchname NOT " . $like . " '%keygen%' and searchname NOT " . $like . " '%advanced%' AND nzbstatus = 1 AND categoryid NOT IN (4000, 4010, 4020, 4030, 4040, 4050, 4060, 4070, 7000, 7010)" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Passworded');
	return $delcount;
}

// Anything that is 1 part and smaller than 1MB and not in MP3/books.
function deleteSize($and)
{
	$db = new DB();
	$sql = $db->prepare("SELECT id, guid, searchname FROM releases WHERE totalpart = 1 AND size < 1000000 AND categoryid NOT IN (8000, 8010, 8020, 8030, 8040, 8050, 8060, 3010)" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Size');
	return $delcount;
}

// More than 1 part, less than 40MB, sample in name. TV/Movie sections.
function deleteSample($and)
{
	$db = new DB();
	$like = 'ILIKE';
	if ($db->dbSystem() == 'mysql') {
		$like = 'LIKE';
	}
	$sql = $db->prepare("SELECT id, guid, searchname FROM releases WHERE totalpart > 1 AND name " . $like . " '%sample%' AND size < 40000000 AND categoryid IN (5010, 5020, 5030, 5040, 5050, 5060, 5070, 5080, 2010, 2020, 2030, 2040, 2050, 2060)" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, 'Sample');
	return $delcount;
}

// Anything with a scr file in the filename/subject.
function deleteScr($and)
{
	$db = new DB();
	if ($db->dbSystem() == 'mysql') {
		$regex = "(rf.name REGEXP '[.]scr$' OR r.name REGEXP '[.]scr[$ \"]')";
	} else {
		$regex = "(rf.name ~ '[.]scr$' OR r.name ~ '[.]scr[$ \"]')";
	}

	$sql = $db->prepare("SELECT r.id, r.guid, r.searchname FROM releases r LEFT JOIN releasefiles rf ON rf.releaseid = r.id WHERE {$regex}" . $and);
	$sql->execute();
	$delcount = deleteReleases($sql, '.scr');
	return $delcount;
}

// Use the site blacklists to delete releases.
function deleteBlacklist($and)
{
	$db = new DB();
	//$binaries = new Binaries();

	$regexes = $db->query('SELECT regex, id, groupname, msgcol FROM binaryblacklist WHERE status = 1 AND optype = 1');
	$delcount = 0;
	$count = count($regexes);
	if ($count > 0) {
		foreach ($regexes as $regex) {

			$rMethod = ($db->dbSystem() === 'mysql' ? 'REGEXP' : '~');

			$regexsql = '';
			switch ((int) $regex['msgcol']) {
				case Binaries::BLACKLIST_FIELD_SUBJECT:
					$regexsql = "LEFT JOIN releasefiles rf ON rf.releaseid = r.id WHERE (rf.name {$rMethod} " .
						$db->escapeString($regex['regex']) .
						" OR r.name {$rMethod} " .
						$db->escapeString($regex['regex']) .
						" OR r.searchname {$rMethod} " .
						$db->escapeString($regex['regex']) .
						")";
					break;
				case Binaries::BLACKLIST_FIELD_FROM:
					$regexsql = "WHERE r.fromname {$rMethod} " . $db->escapeString($regex['regex']);
					break;
				case Binaries::BLACKLIST_FIELD_MESSAGEID:
					break;
			}

			if ($regexsql === '') {
				continue;
			}

			// Get the group ID if the regex is set to work against a group.
			$groupID = '';
			if (strtolower($regex['groupname']) !== 'alt.binaries.*') {
				$groupIDs = $db->query('SELECT id FROM groups WHERE name ' . $rMethod . $db->escapeString($regex['groupname']));
				$gIDcount = count($groupIDs);
				if ($gIDcount === 0) {
					continue;
				} elseif ($gIDcount === 1) {
					$groupIDs = $groupIDs[0]['id'];
				} else {
					$string = '';
					foreach ($groupIDs as $ID) {
						$string .= $ID['id'] . ',';
					}
					$groupIDs = (substr($string, 0, -1));
				}

				$groupID = ' AND r.groupid in (' . $groupIDs . ') ';
			}

			$sql = $db->prepare("SELECT r.id, r.guid, r.searchname FROM releases r " . $regexsql . $groupID . $and);
			$sql->execute();
			$delcount += deleteReleases($sql, 'Blacklist ' . $regex['id']);
		}
	}
	return $delcount;
}

$totalDeleted = $gibberishDeleted = $hashedDeleted = $shortDeleted = $executableDeleted = $installBinDeleted = $PURLDeleted = $PassDeleted = $sizeDeleted = $sampleDeleted = $scrDeleted = $blacklistDeleted = 0;

if (isset($argv[3])) {
	switch ($argv[3]) {
		case 'gibberish':
			$gibberishDeleted = deleteGibberish($and);
			break;
		case 'hashed':
			$hashedDeleted = deleteHashed($and);
			break;
		case 'short':
			$shortDeleted = deleteShort($and);
			break;
		case 'executable':
			$executableDeleted = deleteExecutable($and);
			break;
		case 'installbin':
			$installBinDeleted = deleteInstallBin($and);
			break;
		case 'passwordurl':
			$PURLDeleted = deletePasswordURL($and);
			break;
		case 'passworded':
			$PURLDeleted = deletePassworded($and);
			break;
		case 'size':
			$sizeDeleted = deleteSize($and);
			break;
		case 'sample':
			$sampleDeleted = deleteSample($and);
			break;
		case 'scr':
			$scrDeleted = deleteScr($and);
			break;
		case 'blacklist':
			$blacklistDeleted = deleteBlacklist($and);
			break;
		default:
			exit("Wrong third argument.\n");
	}
} else {
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

$totalDeleted = $totalDeleted + $gibberishDeleted + $hashedDeleted + $shortDeleted + $executableDeleted + $installBinDeleted + $PURLDeleted + $PassDeleted + $sizeDeleted + $sampleDeleted + $scrDeleted + $blacklistDeleted;

if ($totalDeleted > 0) {
	echo $c->header("Total Removed: " . $totalDeleted);
	if ($gibberishDeleted > 0) {
		echo $c->primary("Gibberish    : " . $gibberishDeleted);
	}
	if ($hashedDeleted > 0) {
		echo $c->primary("Hashed       : " . $hashedDeleted);
	}
	if ($shortDeleted > 0) {
		echo $c->primary("Short        : " . $shortDeleted);
	}
	if ($executableDeleted > 0) {
		echo $c->primary("Executable   : " . $executableDeleted);
	}
	if ($installBinDeleted > 0) {
		echo $c->primary("install.bin  : " . $installBinDeleted);
	}
	if ($PURLDeleted > 0) {
		echo $c->primary("PURL         : " . $PURLDeleted);
	}
	if ($PassDeleted > 0) {
		echo $c->primary("Passworded : " . $PassDeleted);
	}
	if ($sizeDeleted > 0) {
		echo $c->primary("Size         : " . $sizeDeleted);
	}
	if ($sampleDeleted > 0) {
		echo $c->primary("Sample       : " . $sampleDeleted);
	}
	if ($scrDeleted > 0) {
		echo $c->primary(".scr         : " . $scrDeleted);
	}
	if ($blacklistDeleted > 0) {
		echo $c->primary("Blacklist    : " . $blacklistDeleted);
	}
} else {
	exit($c->info("Nothing was found to delete."));
}
