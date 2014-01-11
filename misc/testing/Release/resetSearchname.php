<?php

/* This script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.
 * Type php resetSearchname.php to see detailed info. */

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'namecleaning.php';
//require_once nZEDb_LIB . 'namefixer.php';
//require_once nZEDb_LIB . 'consoletools.php';
//require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI();

if (isset($argv[1]) && $argv[1] == "full") {
	$db = new DB();
	$res = $db->query("SELECT releases.id, releases.name, groups.name AS gname FROM releases INNER JOIN groups ON releases.groupid = groups.id");

	if (count($res) > 0) {
		echo $c->header("Going to recreate all search names, recategorize them and fix the names with namefixer, this can take a while.");
		$done = 0;
		$timestart = TIME();
		$consoletools = new ConsoleTools();
		foreach ($res as $row)
		{
			$nc = new NameCleaning();
			$newname = $nc->releaseCleaner($row['name'], $row['gname']);
			if (is_array($newname)) {
				$newname = $newname['cleansubject'];
			}
			$db->queryExec(sprintf("UPDATE releases SET searchname = %s WHERE id = %d", $db->escapeString($newname), $row['id']));
			$done++;
			$consoletools->overWritePrimary("Renaming:" . $consoletools->percentString($done, count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo $c->primary("\n" . $done . " releases renamed in " . $timenc . ".\nNow the releases will be recategorized.");

		$releases = new Releases();
		$releases->resetCategorize();
		$categorized = $releases->categorizeRelease("name", "", true);
		$timecat = $consoletools->convertTime(TIME() - $timestart);
		echo $c->primary("\nFinished categorizing " . $categorized . " releases in " . $timecat . ".\nFinally, the releases will be fixed using the NFO/filenames.");

		$namefixer = new NameFixer();
		$namefixer->fixNamesWithNfo(2, 1, 1, 1);
		$namefixer->fixNamesWithFiles(2, 1, 1, 1);
		$timetotal = $consoletools->convertTime(TIME() - $timestart);
		echo $c->header("\nFinished recreating search names / recategorizing / refixing names in " . $timetotal);
	} else {
		exit($c->info("You have no releases in the DB."));
	}
} else if (isset($argv[1]) && $argv[1] == "limited") {
	$db = new DB();
	$res = $db->query("SELECT releases.id, releases.name, groups.name AS gname FROM releases INNER JOIN groups ON releases.groupid = groups.id WHERE (bitwise & 4) = 0)");

	if (count($res) > 0) {
		echo $c->header("Going to recreate search names that have not been fixed with namefixer, recategorize them, and fix them with namefixer, this can take a while.");
		$done = 0;
		$timestart = TIME();
		$consoletools = new ConsoleTools();
		foreach ($res as $row)
		{
			$nc = new NameCleaning();
			$newname = $nc->releaseCleaner($row['name'], $row['gname']);
			if (is_array($newname)) {
				$newname = $newname['cleansubject'];
			}
			$db->queryExec(sprintf("UPDATE releases SET searchname = %s WHERE id = %d", $db->escapeString($newname), $row['id']));
			$done++;
			$consoletools->overWritePrimary("Renaming:" . $consoletools->percentString($done, count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo $c->header($done . " releases renamed in " . $timenc . ".\nNow the releases will be recategorized.");

		$releases = new Releases();
		$releases->resetCategorize("WHERE (bitwise & 4) = 0");
		$categorized = $releases->categorizeRelease("name", "WHERE (bitwise & 4) = 0", true);
		$timecat = $consoletools->convertTime(TIME() - $timestart);
		echo $c->header("Finished categorizing " . $categorized . " releases in " . $timecat . ".\nFinally, the releases will be fixed using the NFO/filenames.");

		$namefixer = new NameFixer();
		$namefixer->fixNamesWithNfo(2, 1, 1, 1);
		$namefixer->fixNamesWithFiles(2, 1, 1, 1);
		$timetotal = $consoletools->convertTime(TIME() - $timestart);
		echo $c->header("Finished recreating search names / recategorizing / refixing names in " . $timetotal);
	} else {
		exit($c->info("You have no releases in the DB."));
	}
} else if (isset($argv[1]) && $argv[1] == "reset") {
	$db = new DB();
	$res = $db->query("SELECT releases.id, releases.name, groups.name AS gname FROM releases INNER JOIN groups ON releases.groupid = groups.id");

	if (count($res) > 0) {
		echo $c->header("Going to reset search names, this can take a while.");
		$done = 0;
		$timestart = TIME();
		$consoletools = new ConsoleTools();
		foreach ($res as $row)
		{
			$nc = new NameCleaning();
			$newname = $nc->releaseCleaner($row['name'], $row['gname']);
			if (is_array($newname)) {
				$newname = $newname['cleansubject'];
			}
			$db->queryExec(sprintf("UPDATE releases SET searchname = %s where id = %d", $db->escapeString($newname), $row['id']));
			$done++;
			$consoletools->overWritePrimary("Renaming:" . $consoletools->percentString($done, count($res)));
		}
		$timenc = $consoletools->convertTime(TIME() - $timestart);
		echo $c->header($done . " releases renamed in " . $timenc);
	}
} else {
	exit($c->error("\nThis script runs the subject names through namecleaner to create a clean search name, it also recategorizes and runs the releases through namefixer.\n"
					. "php $argv[0] full              ...: To run this, recategorize and refix release names on all releases.\n"
					. "php $argv[0] limited           ...: To run this on releases that have not had their names fixed, then categorizing them.\n"
					. "php $argv[0] reset             ...: To just reset searchnames.\n"));
}
