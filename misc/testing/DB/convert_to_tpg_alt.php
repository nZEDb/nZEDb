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

$actgroups = $db->query("SELECT DISTINCT group_id from collections");

echo $c->info("Creating new collections, binaries, and parts tables for each group that has collections.");

foreach ($actgroups as $group) {
	$db->queryExec("DROP TABLE IF EXISTS collections_" . $group['group_id']);
	$db->queryExec("DROP TABLE IF EXISTS binaries_" . $group['group_id']);
	$db->queryExec("DROP TABLE IF EXISTS parts_" . $group['group_id']);
	if ($db->newtables($group['group_id']) === false) {
		exit($c->error("\nThere is a problem creating new parts/files tables for group ${group['name']}.\n"));
	}
}

$collections_rows = $db->queryDirect("SELECT group_id FROM collections GROUP BY group_id");

echo $c->info("Counting parts, this could table a few minutes.");
$parts_count = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM parts");

$i = 0;
foreach ($collections_rows as $row) {
	$groupName = $groups->getByNameByID($row['group_id']);
	echo $c->header("Processing ${groupName}");
	//collection
	$db->queryExec("INSERT IGNORE INTO collections_" . $row['group_id'] . " (subject, fromname, date, xref, totalfiles, group_id, collectionhash, dateadded, filecheck, filesize, releaseid) "
		. "SELECT subject, fromname, date, xref, totalfiles, group_id, collectionhash, dateadded, filecheck, filesize, releaseid FROM collections WHERE group_id = ${row['group_id']}");
	$collections = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM collections where group_id = " . $row['group_id']);
	$ncollections = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM collections_" . $row['group_id']);
	echo $c->primary("Group ${groupName}, Collections = ${collections['cnt']} [${ncollections['cnt']}]");

	//binaries
	$db->queryExec("INSERT IGNORE INTO binaries_${row['group_id']} (name, filenumber, totalparts, binaryhash, partcheck, partsize, collectionid) "
		. "SELECT name, filenumber, totalparts, binaryhash, partcheck, partsize, n.id FROM binaries b "
		. "INNER JOIN collections c ON b.collectionid = c.id "
		. "INNER JOIN collections_${row['group_id']} n ON c.collectionhash = n.collectionhash AND c.group_id = ${row['group_id']}");
	$binaries = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries b INNER JOIN collections c ON  b.collectionid = c.id where c.group_id = ${row['group_id']}");
	$nbinaries = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries_${row['group_id']}");
	echo $c->primary("Group ${groupName}, Binaries = ${binaries['cnt']} [${nbinaries['cnt']}]");

	//parts
	$db->queryExec("INSERT IGNORE INTO parts_${row['group_id']} (messageid, number, partnumber, size, binaryid) "
		. "SELECT messageid, number, partnumber, size, n.id FROM parts p "
		. "INNER JOIN binaries b ON p.binaryid = b.id "
		. "INNER JOIN binaries_${row['group_id']} n ON b.binaryhash = n.binaryhash "
		. "INNER JOIN collections_${row['group_id']} c on c.id = n.collectionid AND c.group_id = ${row['group_id']}");
	$parts = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM parts p INNER JOIN binaries b ON p.binaryid = b.id INNER JOIN collections c ON b.collectionid = c.id WHERE c.group_id = ${row['group_id']}");
	$nparts = $db->queryOneRow("SELECT COUNT(*) AS cnt FROM parts_${row['group_id']}");
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
