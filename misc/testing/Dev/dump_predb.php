<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

$db = new DB();
$c = new ColorCLI();

if (isset($argv[2])) {
	if (!preg_match('/^\//', $argv[2])) {
		$path = getcwd() . '/' . $argv[2];
	} else {
		$path = $argv[2];
	}
}

if (isset($argv[1]) && $argv[1] == 'export' && isset($argv[2])) {
	if (!preg_match('/\.csv$/', $path)) {
		$path = dirname($path) . '/' . basename($path) . '/prebd_dump.csv';
	} else {
		$path = $path;
	}
	if (!preg_match('/^\//', $path)) {
		$path = getcwd() . '/' . $path;
	}

	if (file_exists($path) && is_file($path)) {
		unlink($path);
	}
	if (isset($argv[3])) {
		$table = $argv[3];
	} else {
		$table = 'predb';
	}
	echo $c->header("SELECT title, nfo, size, category, predate, adddate, source, md5, requestid, g.name FROM " . $table . " p LEFT OUTER JOIN groups g ON p.groupid = g.id INTO OUTFILE '" . $path . "' FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n';n");
	$db->queryDirect("SELECT title, nfo, size, category, predate, adddate, source, md5, requestid, g.name FROM " . $table . " p LEFT OUTER JOIN groups g ON p.groupid = g.id INTO OUTFILE '" . $path . "' FIELDS TERMINATED BY '\t\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n'");
} else if (isset($argv[1]) && ($argv[1] == 'local' || $argv[1] == 'remote') && isset($argv[2]) && is_file($argv[2])) {
	if (!preg_match('/^\//', $path)) {
		$path = require_once getcwd() . '/' . $argv[2];
	}
	if (isset($argv[3])) {
		$table = $argv[3];
	} else {
		$table = 'predb';
	}

	// Create temp table to allow updating
	echo $c->info("Creating temporary table");
	$db->queryExec('DROP TABLE IF EXISTS tmp_pre');
	$db->queryExec('CREATE TABLE tmp_pre LIKE predb');

	// Drop indexes on tmp_pre
	$db->queryExec('ALTER TABLE tmp_pre DROP INDEX `ix_predb_md5`, DROP INDEX `ix_predb_nfo`, DROP INDEX `ix_predb_predate`, DROP INDEX `ix_predb_adddate`, DROP INDEX `ix_predb_source`, DROP INDEX `ix_predb_title`, DROP INDEX `ix_predb_requestid`');
	$db->queryExec('ALTER TABLE tmp_pre ADD COLUMN groupname VARCHAR (255)');

	// Import file into tmp_pre
	if ($argv[1] == 'remote') {
		echo $c->header("LOAD DATA LOCAL INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, category, predate, adddate, source, md5, requestid, groupname);");
		$db->queryDirect("LOAD DATA LOCAL INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\t\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n' (title, nfo, size, category, predate, adddate, source, md5, requestid, groupname)");
	} else {
		echo $c->header("LOAD DATA INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, category, predate, adddate, source, md5, requestid, groupname);");
		$db->queryDirect("LOAD DATA INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\t\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n' (title, nfo, size, category, predate, adddate, source, md5, requestid, groupname)");
	}

	// Insert and update table
	echo $c->primary('INSERT INTO ' . $table . " (title, nfo, size, category, predate, adddate, source, md5, requestid, groupid) SELECT t.title, t.nfo, t.size, t.category, t.predate, t.adddate, t.source, t.md5, t.requestid, IF(g.id IS NOT NULL, g.id, 0) FROM tmp_pre t LEFT OUTER JOIN groups g ON t.groupname = g.name ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo is null, t.nfo, predb.nfo), predb.size = IF(predb.size is null, t.size, predb.size), predb.category = IF(predb.category is null, t.category, predb.category), predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid), predb.groupid = IF(g.id IS NOT NULL, g.id, 0);\n");
	$db->queryDirect('INSERT INTO ' . $table . ' (title, nfo, size, category, predate, adddate, source, md5, requestid, groupid) SELECT t.title, t.nfo, t.size, t.category, t.predate, t.adddate, t.source, t.md5, t.requestid, IF(g.id IS NOT NULL, g.id, 0) FROM tmp_pre t LEFT OUTER JOIN groups g ON t.groupname = g.name ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo is null, t.nfo, predb.nfo), predb.size = IF(predb.size is null, t.size, predb.size), predb.category = IF(predb.category is null, t.category, predb.category), predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid), predb.groupid = IF(g.id IS NOT NULL, g.id, 0)');

	// Drop tmp_pre table
	$db->queryExec('DROP TABLE IF EXISTS tmp_pre');
} else {
	exit($c->error("\nThis script can export or import a predb dump file. You may use the full path, or a relative path.\n"
					. "For importing, the script insert new rows and update existing matched rows. For databases not on the local system, use remote, else use local.\n"
					. "For exporting, the path must be writeable by mysql, any existing file[prebd_dump.csv] will be overwritten.\n\n"
					. "php $argv[0] export /path/to/write/to                     ...: To export.\n"
					. "php $argv[0] [remote | local] /path/to/filename           ...: To import.\n"));
}
