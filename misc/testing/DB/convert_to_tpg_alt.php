<?php
require_once realpath(dirname(__DIR__, 3) . '/app/config/bootstrap.php');

use app\models\Groups as Group;
use app\models\Tables;
use nzedb\ConsoleTools;
use nzedb\Groups;
use nzedb\db\DB;

$pdo = new DB();

if (!isset($argv[1]) || $argv[1] != 'true') {
	exit($pdo->log->error("\nThis script will move all collections, binaries, parts into tables per group.\n\n"
			. "php $argv[0] true                ...: To process all parts and leave the parts/binaries/collections tables intact.\n"
			. "php $argv[0] true truncate       ...: To process all parts and truncate parts/binaries/collections tables after completed.\n"));
}

$start = time();
$consoleTools = new ConsoleTools(['ColorCLI' => $pdo->log]);
$groups = new Groups(['Settings' => $pdo]);

$actgroups = $pdo->query("SELECT DISTINCT groups_id from collections");

echo $pdo->log->info("Creating new collections, binaries, and parts tables for each group that has collections.");

foreach ($actgroups as $group) {
	$pdo->queryExec("DROP TABLE IF EXISTS collections_" . $group['groups_id']);
	$pdo->queryExec("DROP TABLE IF EXISTS binaries_" . $group['groups_id']);
	$pdo->queryExec("DROP TABLE IF EXISTS parts_" . $group['groups_id']);
	if ((Tables::createTPGTablesForId($group['groups_id']) === false)) {
		exit($pdo->log->error("\nThere is a problem creating new parts/files tables for group ${group['name']}.\n"));
	}
}

$collections_rows = $pdo->queryDirect("SELECT groups_id FROM collections GROUP BY groups_id");

echo $pdo->log->info("Counting parts, this could table a few minutes.");
$parts_count = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM parts");

$i = 0;
if ($collections_rows instanceof \Traversable) {
	foreach ($collections_rows as $row) {
		$groupName = Group::getNameByID($row['groups_id']);
		echo $pdo->log->header("Processing ${groupName}");
		//collection
		$pdo->queryExec("INSERT IGNORE INTO collections_" . $row['groups_id'] . " (subject, fromname, date, xref, totalfiles, groups_id, collectionhash, dateadded, filecheck, filesize, releases_id) "
			. "SELECT subject, fromname, date, xref, totalfiles, groups_id, collectionhash, dateadded, filecheck, filesize, releases_id FROM collections WHERE groups_id = ${row['groups_id']}");
		$collections = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM collections where groups_id = " . $row['groups_id']);
		$ncollections = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM collections_" . $row['groups_id']);
		echo $pdo->log->primary("Group ${groupName}, Collections = ${collections['cnt']} [${ncollections['cnt']}]");

		//binaries
		$pdo->queryExec("INSERT IGNORE INTO binaries_${row['groups_id']} (name, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize, collections_id) "
			. "SELECT name, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize, n.id FROM binaries b "
			. "INNER JOIN collections c ON b.collections_id = c.id "
			. "INNER JOIN collections_${row['groups_id']} n ON c.collectionhash = n.collectionhash AND c.groups_id = ${row['groups_id']}");
		$binaries = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries b INNER JOIN collections c ON  b.collections_id = c.id where c.groups_id = ${row['groups_id']}");
		$nbinaries = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM binaries_${row['groups_id']}");
		echo $pdo->log->primary("Group ${groupName}, Binaries = ${binaries['cnt']} [${nbinaries['cnt']}]");

		//parts
		$pdo->queryExec("INSERT IGNORE INTO parts_${row['groups_id']} (binaries_id, messageid, number, partnumber, size) "
			. "SELECT n.id, messageid, number, partnumber, size FROM parts p "
			. "INNER JOIN binaries b ON p.binaries_id = b.id "
			. "INNER JOIN binaries_${row['groups_id']} n ON b.binaryhash = n.binaryhash "
			. "INNER JOIN collections_${row['groups_id']} c on c.id = n.collections_id AND c.groups_id = ${row['groups_id']}");
		$parts = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM parts p INNER JOIN binaries b ON p.binaries_id = b.id INNER JOIN collections c ON b.collections_id = c.id WHERE c.groups_id = ${row['groups_id']}");
		$nparts = $pdo->queryOneRow("SELECT COUNT(*) AS cnt FROM parts_${row['groups_id']}");
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

echo $pdo->log->header("Processed: ${i} groups and " . number_format($parts_count['cnt']) . " parts in " . $consoleTools->convertTimer(TIME() - $start));
