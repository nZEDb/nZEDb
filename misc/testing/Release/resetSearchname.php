<?php
/* This script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.
 * Type php resetSearchname.php to see detailed info. */
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$sphinx = new \SphinxSearch();

$show = 2;
if (isset($argv[2]) && $argv[2] === 'show') {
	$show = 1;
}

if (isset($argv[1]) && $argv[1] == "full") {
	$res = $pdo->query("SELECT releases.id, releases.name, releases.fromname, releases.size, groups.name AS gname FROM releases INNER JOIN groups ON releases.group_id = groups.id");

	if (count($res) > 0) {
		echo $pdo->log->header("Going to recreate all search names, recategorize them and fix the names with namefixer, this can take a while.");
		$done = 0;
		$timestart = time();
		$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
		$rc = new \ReleaseCleaning($pdo);
		foreach ($res as $row) {
			$newname = $rc->releaseCleaner($row['name'], $row['fromname'], $row['size'], $row['gname']);
			if (is_array($newname)) {
				$newname = $newname['cleansubject'];
			}
			$newname = $pdo->escapeString($newname);
			$pdo->queryExec(sprintf("UPDATE releases SET searchname = %s WHERE id = %d", $newname, $row['id']));
			$sphinx->updateReleaseSearchName($newname, $row['id']);
			$done++;
			$consoletools->overWritePrimary("Renaming:" . $consoletools->percentString($done, count($res)));
		}
		$timenc = $consoletools->convertTime(time() - $timestart);
		echo $pdo->log->primary("\n" . $done . " releases renamed in " . $timenc . ".\nNow the releases will be recategorized.");

		$releases = new \ProcessReleases(['Settings' => $pdo, 'ConsoleTools' => $consoletools, 'ReleaseCleaning' => $rc]);
		$releases->resetCategorize();
		$categorized = $releases->categorizeRelease("name", "");
		$timecat = $consoletools->convertTime(time() - $timestart);
		echo $pdo->log->primary("\nFinished categorizing " . $categorized . " releases in " . $timecat . ".\nFinally, the releases will be fixed using the NFO/filenames.");

		$namefixer = new \NameFixer(['Settings' => $pdo, 'ConsoleTools' => $consoletools]);
		$namefixer->fixNamesWithNfo(2, 1, 1, 1, $show);
		$namefixer->fixNamesWithFiles(2, 1, 1, 1, $show);
		$timetotal = $consoletools->convertTime(time() - $timestart);
		echo $pdo->log->header("\nFinished recreating search names / recategorizing / refixing names in " . $timetotal);
	} else {
		exit($pdo->log->info("You have no releases in the DB."));
	}
} else if (isset($argv[1]) && $argv[1] == "limited") {
	$pdo = new Settings();
	$res = $pdo->query("SELECT releases.id, releases.name, releases.fromname, releases.size, groups.name AS gname FROM releases INNER JOIN groups ON releases.group_id = groups.id WHERE isrenamed = 0");

	if (count($res) > 0) {
		echo $pdo->log->header("Going to recreate search names that have not been fixed with namefixer, recategorize them, and fix them with namefixer, this can take a while.");
		$done = 0;
		$timestart = TIME();
		$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
		$rc = new \ReleaseCleaning($pdo);
		foreach ($res as $row) {
			$newname = $rc->releaseCleaner($row['name'], $row['fromname'], $row['size'], $row['gname']);
			if (is_array($newname)) {
				$newname = $newname['cleansubject'];
			}
			$newname = $pdo->escapeString($newname);
			$pdo->queryExec(sprintf("UPDATE releases SET searchname = %s WHERE id = %d", $newname, $row['id']));
			$sphinx->updateReleaseSearchName($newname, $row['id']);
			$done++;
			$consoletools->overWritePrimary("Renaming:" . $consoletools->percentString($done, count($res)));
		}
		$timenc = $consoletools->convertTime(time() - $timestart);
		echo $pdo->log->header($done . " releases renamed in " . $timenc . ".\nNow the releases will be recategorized.");

		$releases = new \ProcessReleases(['Settings' => $pdo, 'ConsoleTools' => $consoletools, 'ReleaseCleaning' => $rc]);
		$releases->resetCategorize("WHERE isrenamed = 0");
		$categorized = $releases->categorizeRelease("name", "WHERE isrenamed = 0");
		$timecat = $consoletools->convertTime(time() - $timestart);
		echo $pdo->log->header("Finished categorizing " . $categorized . " releases in " . $timecat . ".\nFinally, the releases will be fixed using the NFO/filenames.");

		$namefixer = new \NameFixer(['Settings' => $pdo, 'ConsoleTools' => $consoletools]);
		$namefixer->fixNamesWithNfo(2, 1, 1, 1, $show);
		$namefixer->fixNamesWithFiles(2, 1, 1, 1, $show);
		$timetotal = $consoletools->convertTime(time() - $timestart);
		echo $pdo->log->header("Finished recreating search names / recategorizing / refixing names in " . $timetotal);
	} else {
		exit($pdo->log->info("You have no releases in the DB."));
	}
} else if (isset($argv[1]) && $argv[1] == "reset") {
	$pdo = new Settings();
	$res = $pdo->query("SELECT releases.id, releases.name, releases.fromname, releases.size, groups.name AS gname FROM releases INNER JOIN groups ON releases.group_id = groups.id");

	if (count($res) > 0) {
		echo $pdo->log->header("Going to reset search names, this can take a while.");
		$done = 0;
		$timestart = time();
		$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
		foreach ($res as $row) {
			$rc = new \ReleaseCleaning($pdo);
			$newname = $rc->releaseCleaner($row['name'], $row['fromname'], $row['size'], $row['gname']);
			if (is_array($newname)) {
				$newname = $newname['cleansubject'];
			}
			$newname = $pdo->escapeString($newname);
			$pdo->queryExec(sprintf("UPDATE releases SET searchname = %s where id = %d", $newname, $row['id']));
			$sphinx->updateReleaseSearchName($newname, $row['id']);
			$done++;
			$consoletools->overWritePrimary("Renaming:" . $consoletools->percentString($done, count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo $pdo->log->header($done . " releases renamed in " . $timenc);
	}
} else {
	exit($pdo->log->error("\nThis script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.\n"
			. "php resetSearchname.php full              ...: To run this, recategorize and refix release names on all releases.\n"
			. "php resetSearchname.php limited           ...: To run this on releases that have not had their names fixed, then categorizing them.\n"
			. "php resetSearchname.php reset             ...: To just reset searchnames.\n"));
}
