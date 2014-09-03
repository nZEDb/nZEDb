<?php
require_once(dirname(__FILE__) . '/../../../www/config.php');

use nzedb\db\Settings;

$pdo = new Settings();

if (!isset($argv[1]) || $argv[1] != 'true') {
	exit($pdo->log->error("\nThis script will move all collections, binaries, parts into tables per group.\n\n"
			. "php $argv[0] true                ...: To process all parts and leave the parts/binaries/collections tables intact.\n"
			. "php $argv[0] true truncate       ...: To process all parts and truncate parts/binaries/collections tables after completed.\n"));
}

$start = time();
$consoleTools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
$groups = new \Groups(['Settings' => $pdo]);

$actgroups = $pdo->query("SELECT DISTINCT group_id from collections");

echo $pdo->log->info("Creating new collections, binaries, and parts tables for each group that has collections.");

foreach ($actgroups as $group) {
	$pdo->queryExec("DROP TABLE IF EXISTS collections_" . $group['group_id']);
	$pdo->queryExec("DROP TABLE IF EXISTS binaries_" . $group['group_id']);
	$pdo->queryExec("DROP TABLE IF EXISTS parts_" . $group['group_id']);
	if ($groups->createNewTPGTables($group['group_id']) === false) {
		exit($pdo->log->error("\nThere is a problem creating new parts/files tables for group ${group['name']}.\n"));
	}
}

$collections_rows = $pdo->queryDirect("SELECT group_id FROM collections GROUP BY group_id");

echo $pdo->log->info("Counting parts, this could table a few minutes.");
$parts_count = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM parts");

$i = 0;
if ($collections_rows instanceof \Traversable) {
	foreach ($collections_rows as $row) {
		$groupName = $groups->getByNameByID($row['group_id']);
		echo $pdo->log->header("Processing ${groupName}");
		//collection
		$pdo->queryExec("INSERT IGNORE INTO collections_" . $row['group_id'] . " (subject, fromname, date, xref, totalfiles, group_id, collectionhash, dateadded, filecheck, filesize, releaseid) "
			. "SELECT subject, fromname, date, xref, totalfiles, group_id, collectionhash, dateadded, filecheck, filesize, releaseid FROM collections WHERE group_id = ${row['group_id']}");
		$collections = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM collections where group_id = " . $row['group_id']);
		$ncollections = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM collections_" . $row['group_id']);
		echo $pdo->log->primary("Group ${groupName}, Collections = ${collections['cnt']} [${ncollections['cnt']}]");

		//binaries
		$pdo->queryExec("INSERT IGNORE INTO binaries_${row['group_id']} (name, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize, collectionid) "
			. "SELECT name, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize, n.id FROM binaries b "
			. "INNER JOIN collections c ON b.collectionid = c.id "
			. "INNER JOIN collections_${row['group_id']} n ON c.collectionhash = n.collectionhash AND c.group_id = ${row['group_id']}");
		$binaries = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries b INNER JOIN collections c ON  b.collectionid = c.id where c.group_id = ${row['group_id']}");
		$nbinaries = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries_${row['group_id']}");
		echo $pdo->log->primary("Group ${groupName}, Binaries = ${binaries['cnt']} [${nbinaries['cnt']}]");

		//parts
		$pdo->queryExec("INSERT IGNORE INTO parts_${row['group_id']} (messageid, number, partnumber, size, binaryid, collection_id) "
			. "SELECT messageid, number, partnumber, size, n.id, c.id FROM parts p "
			. "INNER JOIN binaries b ON p.binaryid = b.id "
			. "INNER JOIN binaries_${row['group_id']} n ON b.binaryhash = n.binaryhash "
			. "INNER JOIN collections_${row['group_id']} c on c.id = n.collectionid AND c.group_id = ${row['group_id']}");
		$parts = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM parts p INNER JOIN binaries b ON p.binaryid = b.id INNER JOIN collections c ON b.collectionid = c.id WHERE c.group_id = ${row['group_id']}");
		$nparts = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM parts_${row['group_id']}");
		echo $pdo->log->primary("Group ${groupName}, Parts = ${parts['cnt']} [${nparts['cnt']}]\n");
		$i++;
	}
}

if (isset($argv[2]) && $argv[2] == 'truncate') {
	echo $pdo->log->info("Truncating collections, binaries and parts tables.");
	$pdo->queryExec("TRUNCATE TABLE collections");
	$pdo->queryExec("TRUNCATE TABLE binaries");
	$pdo->queryExec("TRUNCATE TABLE parts");
}

//set tpg active
$pdo->queryExec("UPDATE settings SET value = 1 WHERE setting = 'tablepergroup'");

echo $pdo->log->header("Processed: ${i} groups and " . number_format($parts_count['cnt']) . " parts in " . $consoleTools->convertTimer(TIME() - $start));
