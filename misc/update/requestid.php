<?php
require_once dirname(__FILE__) . '/config.php';

use nzedb\db\DB;

$c = new ColorCLI();
if (!isset($argv[1]) || ( $argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1]))) {
	exit($c->error("\nThis script tries to match a release request ID by group to a PreDB request ID by group doing local lookup only.\n"
			. "In addition an optional final argument is time, in minutes, to check releases that have previously been checked.\n\n"
			. "php requestid.php 1000 show		...: to limit to 1000 sorted by newest postdate and show renaming.\n"
			. "php requestid.php full show		...: to run on full database and show renaming.\n"
			. "php requestid.php all show		...: to run on all hashed releases (including previously renamed) and show renaming.\n"));
}

$db = new DB();
$category = new Category();
$groups = new Groups();
$consoletools = new ConsoleTools();
$namefixer = new NameFixer();

$timestart = TIME();
$counter = $counted = 0;

if (isset($argv[2]) && is_numeric($argv[2])) {
	$time = ' OR r.postdate > NOW() - INTERVAL ' . $argv[2] . ' MINUTE)';
} else if (isset($argv[3]) && is_numeric($argv[3])) {
	$time = ' OR r.postdate > NOW() - INTERVAL ' . $argv[3] . ' MINUTE)';
} else {
	$time = ')';
}

//runs on every release not already PreDB Matched
if (isset($argv[1]) && $argv[1] === "all") {
	$qry = $db->queryDirect("SELECT r.id, r.name, r.categoryid, g.name AS groupname, g.id as gid FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE nzbstatus = 1 AND preid = 0 AND isrequestid = 1");
//runs on all releases not already renamed not already PreDB matched
} else if (isset($argv[1]) && $argv[1] === "full") {
	$qry = $db->queryDirect("SELECT r.id, r.name, r.categoryid, g.name AS groupname, g.id as gid FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE nzbstatus = 1 AND preid = 0 AND (isrenamed = 0 AND isrequestid = 1 " . $time . " AND reqidstatus in (0, -1, -3)");
//runs on all releases not already renamed limited by user not already PreDB matched
} else if (isset($argv[1]) && is_numeric($argv[1])) {
	$qry = $db->queryDirect("SELECT r.id, r.name, r.categoryid, g.name AS groupname, g.id as gid FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE nzbstatus = 1 AND preid = 0 AND (isrenamed = 0 AND isrequestid = 1 " . $time . " AND reqidstatus in (0, -1, -3) ORDER BY postdate DESC LIMIT " . $argv[1]);
}

$total = $qry->rowCount();
if ($total > 0) {
	$precount = $db->queryOneRow('SELECT COUNT(*) AS count FROM predb WHERE requestid > 0');
	echo $c->header("\nComparing " . number_format($total) . ' releases against ' . number_format($precount['count']) . " Local requestID's.");
	sleep(2);

	foreach ($qry as $row) {
		if (!preg_match('/^\[\d+\]/', $row['name']) && !preg_match('/^\[ \d+ \]/', $row['name'])) {
			$db->queryExec('UPDATE releases SET reqidstatus = -2 WHERE id = ' . $row['id']);
			$counter++;
			continue;
		}

		$requestIDtmp = explode(']', substr($row['name'], 1));
		$bFound = false;
		$newTitle = '';

		if (count($requestIDtmp) >= 1) {
			$requestID = (int) trim($requestIDtmp[0]);
			if ($requestID != 0 and $requestID != '') {
				// Do a local lookup first
				$newTitle = localLookup($requestID, $row['groupname'], $row['name']);
				if (is_array($newTitle) && $newTitle['title'] != '') {
					$bFound = true;
				}
			}
		}

		if ($bFound === true) {
			$title = $newTitle['title'];
			$preid = $newTitle['id'];
			$determinedcat = $category->determineCategory($title, $row['gid']);
			$run = $db->queryDirect(sprintf('UPDATE releases set rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, '
					. 'preid = %d, reqidstatus = 1, isrenamed = 1, iscategorized = 1, searchname = %s, categoryid = %d where id = %d', $preid, $db->escapeString($title), $determinedcat, $row['id']));
			if ($row['name'] !== $newTitle) {
				$counted++;
				if (isset($argv[2]) && $argv[2] === 'show') {
					$newcatname = $category->getNameByID($determinedcat);
					$oldcatname = $category->getNameByID($row['categoryid']);

					echo $c->headerOver("\nNew name:  ") . $c->primary($title) .
					$c->headerOver('Old name:  ') . $c->primary($row['name']) .
					$c->headerOver('New cat:   ') . $c->primary($newcatname) .
					$c->headerOver('Old cat:   ') . $c->primary($oldcatname) .
					$c->headerOver('Group:     ') . $c->primary($row['groupname']) .
					$c->headerOver('Method:    ') . $c->primary('requestID local') .
					$c->headerOver('ReleaseID: ') . $c->primary($row['id']);
				}
			}
		} else {
			$db->queryExec('UPDATE releases SET reqidstatus = -3 WHERE id = ' . $row['id']);
		}
		if (!isset($argv[2]) || $argv[2] !== 'show') {
			$consoletools->overWritePrimary("Renamed Releases: [" . number_format($counted) . "] " . $consoletools->percentString(++$counter, $total));
		}
	}
	if ($total > 0) {
		echo $c->header("\nRenamed " . number_format($counted) . " releases in " . $consoletools->convertTime(TIME() - $timestart) . ".");
	} else {
		echo $c->info("\nNothing to do.");
	}
} else {
	echo $c->info("No work to process\n");
}

function localLookup($requestID, $groupName, $oldname)
{
	$db = new DB();
	$groups = new Groups();
	$groupid = $groups->getIDByName($groupName);
	$run = $db->queryOneRow(sprintf("SELECT id, title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
	if (isset($run['title']) && preg_match('/s\d+/i', $run['title']) && !preg_match('/s\d+e\d+/i', $run['title'])) {
		return false;
	}

	if (isset($run['title'])) {
		return array('title' => $run['title'], 'id' => $run['id']);
	}
	if (preg_match('/\[#?a\.b\.teevee\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.teevee');
	} else if (preg_match('/\[#?a\.b\.moovee\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.moovee');
	} else if (preg_match('/\[#?a\.b\.erotica\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.erotica');
	} else if (preg_match('/\[#?a\.b\.foreign\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.mom');
	} else if ($groupName == 'alt.binaries.etc') {
		$groupid = $groups->getIDByName('alt.binaries.teevee');
	}

	$run = $db->queryOneRow(sprintf("SELECT id, title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
	if (isset($run['title'])) {
		return array('title' => $run['title'], 'id' => $run['id']);
	}
}
