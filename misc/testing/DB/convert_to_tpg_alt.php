<?php
require_once(dirname(__FILE__) . '/../../../www/config.php');

use nzedb\db\DB;

$c = new ColorCLI();
if (!isset($argv[1]) || $argv[1] != 'true') {
	exit($c->error("\nThis script will move all collections, binaries, parts into tables per group.\n\n"
			. "php $argv[0] true                ...: To process all parts and leave the parts/binaries/collections tables intact.\n"
			. "php $argv[0] true truncate       ...: To process all parts and truncate parts/binaries/collections tables after completed.\n"));
}

$db = new DB();
$start = TIME();
$consoleTools = new ConsoleTools();
$groups = new Groups();

$actgroups = $db->query("SELECT DISTINCT groupid from collections");

echo $c->info("Creating new collections, binaries, and parts tables for each group that has collections.");

foreach ($actgroups as $group) {
	$db->queryExec("DROP TABLE IF EXISTS collections_" . $group['groupid']);
	$db->queryExec("DROP TABLE IF EXISTS binaries_" . $group['groupid']);
	$db->queryExec("DROP TABLE IF EXISTS parts_" . $group['groupid']);
	if ($db->newtables($group['groupid']) === false) {
		exit($c->error("\nThere is a problem creating new parts/files tables for group ${group['name']}.\n"));
	}
}

$collections_rows = $db->queryDirect("SELECT groupid FROM collections GROUP BY groupid");

echo $c->info("Counting parts, this could table a few minutes.");
$parts_count = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM parts");

$i = 0;
foreach ($collections_rows as $row) {
	$groupName = $groups->getByNameByID($row['groupid']);
	echo $c->header("Processing ${groupName}");
	//collection
	$db->queryExec("INSERT IGNORE INTO collections_" . $row['groupid'] . " (subject, fromname, date, xref, totalfiles, groupid, collectionhash, dateadded, filecheck, filesize, releaseid) "
		. "SELECT subject, fromname, date, xref, totalfiles, groupid, collectionhash, dateadded, filecheck, filesize, releaseid FROM collections WHERE groupid = ${row['groupid']}");
	$collections = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM collections where groupid = " . $row['groupid']);
	$ncollections = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM collections_" . $row['groupid']);
	echo $c->primary("Group ${groupName}, Collections = ${collections['cnt']} [${ncollections['cnt']}]");

	//binaries
	$db->queryExec("INSERT IGNORE INTO binaries_${row['groupid']} (name, filenumber, totalparts, binaryhash, partcheck, partsize, collectionid) "
		. "SELECT name, filenumber, totalparts, binaryhash, partcheck, partsize, n.id FROM binaries b "
		. "INNER JOIN collections c ON b.collectionid = c.id "
		. "INNER JOIN collections_${row['groupid']} n ON c.collectionhash = n.collectionhash AND c.groupid = ${row['groupid']}");
	$binaries = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries b INNER JOIN collections c ON  b.collectionid = c.id where c.groupid = ${row['groupid']}");
	$nbinaries = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries_${row['groupid']}");
	echo $c->primary("Group ${groupName}, Binaries = ${binaries['cnt']} [${nbinaries['cnt']}]");

	//parts
	$db->queryExec("INSERT IGNORE INTO parts_${row['groupid']} (messageid, number, partnumber, size, binaryid) "
		. "SELECT messageid, number, partnumber, size, n.id FROM parts p "
		. "INNER JOIN binaries b ON p.binaryid = b.id "
		. "INNER JOIN binaries_${row['groupid']} n ON b.binaryhash = n.binaryhash "
		. "INNER JOIN collections_${row['groupid']} c on c.id = n.collectionid AND c.groupid = ${row['groupid']}");
	$parts = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM parts p INNER JOIN binaries b ON p.binaryid = b.id INNER JOIN collections c ON b.collectionid = c.id WHERE c.groupid = ${row['groupid']}");
	$nparts = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM parts_${row['groupid']}");
	echo $c->primary("Group ${groupName}, Parts = ${parts['cnt']} [${nparts['cnt']}]\n");
	$i++;
}

if (isset($argv[2]) && $argv[2] == 'truncate') {
	echo $c->info("Truncating collections, binaries and parts tables.");
	$db->queryExec("TRUNCATE TABLE collections");
	$db->queryExec("TRUNCATE TABLE binaries");
	$db->queryExec("TRUNCATE TABLE parts");
}

//set tpg active
$db->queryExec("UPDATE settings SET value = 1 WHERE setting = 'tablepergroup'");

echo $c->header("Processed: ${i} groups and " . number_format($parts_count['cnt']) . " parts in " . $consoleTools->convertTimer(TIME() - $start));
