<?php

require_once dirname(__FILE__) . '/../../../www/config.php';

$db = new DB();
$s = new Sites();
$consoletools = new ConsoleTools();
$site = $s->get();
$timestart = TIME();
$relcount = 0;
$c = new ColorCLI();

if (isset($argv[3])) {
	//	This script removes all releases and nzb files based on poster, searchname, name, groupname, or guid.
	if ($argv[2] == "equals" && ($argv[1] == "searchname" || $argv[1] == "name" || $argv[1] == "guid" || $argv[1] == "fromname")) {
		echo $c->header('SELECT id, guid FROM releases WHERE ' . $argv[1] . ' = ' . $db->escapeString($argv[3]) . ';');
		$relids = $db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE %s = %s", $argv[1], $db->escapeString($argv[3])));
	} else if ($argv[2] == "equals" && ($argv[1] == "categoryid") && is_numeric($argv[3])) {
		echo $c->header('SELECT id, guid FROM releases WHERE categoryid = ' . $argv[3] . ';');
		$relids = $db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d", $argv[3]));
	} else if ($argv[2] == "equals" && ($argv[1] == "groupname")) {
		echo $c->header('SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID  AND g.name = ' . $db->escapeString($argv[3]) . ';');
		$relids = $db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID  AND g.name = %s", $db->escapeString($argv[3])));
	} else if ($argv[2] == "like" && ($argv[1] == "searchname" || $argv[1] == "name" || $argv[1] == "guid" || $argv[1] == "fromname")) {
		$like = ' ILIKE';
		if ($db->dbSystem() == 'mysql') {
			$like = ' LIKE';
		}
		echo $c->header("SELECT id, guid, fromanme FROM releases WHERE " . $argv[1] . $like . " '%" . $argv[3] . "%';");
		$relids = $db->queryDirect("SELECT id, guid, fromname FROM releases WHERE " . $argv[1] . $like . " '%" . $argv[3] . "%'");
	} else if ($argv[2] == "like" && $argv[1] == "groupname") {
		$like = ' ILIKE';
		if ($db->dbSystem() == 'mysql') {
			$like = ' LIKE';
		}
		echo $c->header("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND g.name " . $like . " '%" . $argv[3] . "%';");
		$relids = $db->queryDirect("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND g.name " . $like . " '%" . $argv[3] . "%'");
	} else if ($argv[2] == "equals" && ($argv[1] == "adddate" || $argv[1] == "postdate") && isset($argv[3]) && is_numeric($argv[3])) {
		if ($db->dbSystem() == 'mysql') {
			echo $c->header("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r." . $argv[1] . " > NOW() - INTERVAL " . $argv[3] . " HOUR;");
			$relids = $db->queryDirect("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r." . $argv[1] . " > NOW() - INTERVAL " . $argv[3] . " HOUR");
		} else {
			echo $c->header("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r." . $argv[1] . " > NOW() - INTERVAL '" . $argv[3] . " HOURS';");
			$relids = $db->queryDirect("SELECT r.id, r.guid FROM releases r, groups g WHERE r.groupid = g.ID AND r." . $argv[1] . " > NOW() - INTERVAL '" . $argv[3] . " HOURS'");
		}
	}
} else {
	exit($c->error("\nThis script removes all releases and nzb files from a poster or by searchname or by name or by groupname or by guid or by categoryid or newer than x hours adddate/postdate.\n\n"
		. "php $argv[0] [ fromname, searchname, name, groupname, guid, categoryid, adddate/postdate ] equals [ name, guid, 7010, hours(number) ]\n"
		. "php $argv[0] [ fromname, searchname, name, groupname, guid ] like [ name/guid ]\n"));
}
$total = $relids->rowCount();

if ($argv[1] == "adddate") {
	echo $c->primary("Deleting " . $total . " releases and NZB's for past " . $argv[3] . " hours");
} else {
	echo $c->primary("Deleting " . $total . " releases and NZB's for " . $argv[3]);
}

$releases = new Releases();
foreach ($relids as $relid) {
	$releases->fastDelete($relid['id'], $relid['guid'], $site);
	$relcount++;
	$consoletools->overWriteHeader("Deleting: " . $consoletools->percentString($relcount, $total) . " Time:" . $consoletools->convertTimer(TIME() - $timestart));
}

if ($relcount > 0) {
	echo "\n";
}
echo $c->headerOver("Deleted " . $relcount . " release(s). This script ran for ");
echo $c->header($consoletools->convertTime(TIME() - $timestart));
echo "\n";
