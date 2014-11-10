<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

$path = '';
if (isset($argv[2])) {
	if (!preg_match('/^\//', $argv[2])) {
		$path = getcwd() . '/' . $argv[2];
	} else {
		$path = $argv[2];
	}
}

if (isset($argv[1]) && $argv[1] == 'export' && isset($argv[2])) {
	if (!preg_match('/\.csv$/', $path)) {
		$path = dirname($path) . '/' . basename($path) . '/predb_dump.csv';
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
	echo  $pdo->log->header("SELECT title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, g.name FROM " . $table . " p LEFT OUTER JOIN groups g ON p.group_id = g.id INTO OUTFILE '" . $path . "' FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n';n");
	$pdo->queryDirect("SELECT title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, g.name FROM " . $table . " p LEFT OUTER JOIN groups g ON p.group_id = g.id INTO OUTFILE '" . $path . "' FIELDS TERMINATED BY '\t\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n'");
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
	echo $pdo->log->info("Creating temporary table");
	$pdo->queryExec('DROP TABLE IF EXISTS tmp_pre');
	$pdo->queryExec('CREATE TABLE tmp_pre LIKE predb');

	// Drop indexes on tmp_pre
	$pdo->queryExec('ALTER TABLE tmp_pre DROP INDEX `ix_predb_nfo`, DROP INDEX `ix_predb_predate`, DROP INDEX `ix_predb_source`, DROP INDEX `ix_predb_title`, DROP INDEX `ix_predb_requestid`');
	$pdo->queryExec('ALTER TABLE tmp_pre ADD COLUMN groupname VARCHAR (255)');

	// Import file into tmp_pre
	if ($argv[1] == 'remote') {
		echo  $pdo->log->header("LOAD DATA LOCAL INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);");
		$pdo->queryDirect("LOAD DATA LOCAL INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\t\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname)");
	} else {
		echo  $pdo->log->header("LOAD DATA INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);");
		$pdo->queryDirect("LOAD DATA INFILE '" . $path . "' IGNORE into table tmp_pre FIELDS TERMINATED BY '\t\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname)");
	}

    // Remove any titles where length <=8
    echo $pdo->log->info("Deleting any records where title <=8 from Temporary Table");
    $pdo->queryDirect("DELETE FROM tmp_pre WHERE LENGTH(title) <= 8");

	// Drop triggers on predb
	echo $pdo->log->info("Dropping predb_hashes triggers");
	$pdo->queryDirect("DROP TRIGGER IF EXISTS insert_hashes");
	$pdo->queryDirect("DROP TRIGGER IF EXISTS update_hashes");

	// Insert and update table
	echo $pdo->log->primary('INSERT INTO ' . $table . " (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, group_id)
	SELECT t.title, t.nfo, t.size, t.files, t.filename, t.nuked, t.nukereason, t.category,
	 t.predate, t.source, t.requestid, IF(g.id IS NOT NULL, g.id, 0) FROM tmp_pre t
	 LEFT OUTER JOIN groups g ON t.groupname = g.name
	 ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo is null, t.nfo, predb.nfo),
	 predb.size = IF(predb.size is null, t.size, predb.size),
	 predb.files = IF(predb.files is null, t.files, predb.files),
	 predb.filename = IF(predb.filename = '', t.filename, predb.filename),
	 predb.nuked = IF(t.nuked > 0, t.nuked, predb.nuked),
	 predb.nukereason = IF(t.nuked > 0, t.nukereason, predb.nukereason),
	 predb.category = IF(predb.category is null, t.category, predb.category),
	 predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid),
	 predb.group_id = IF(g.id IS NOT NULL, g.id, 0);\n");
	$pdo->queryDirect('INSERT INTO ' . $table . ' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, group_id)
	SELECT t.title, t.nfo, t.size, t.files, t.filename, t.nuked, t.nukereason, t.category,
	 t.predate, t.source, t.requestid, IF(g.id IS NOT NULL, g.id, 0) FROM tmp_pre t
	 LEFT OUTER JOIN groups g ON t.groupname = g.name
	 ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo is null, t.nfo, predb.nfo),
	 predb.size = IF(predb.size is null, t.size, predb.size),
	 predb.files = IF(predb.files is null, t.files, predb.files),
	 predb.filename = IF(predb.filename = "", t.filename, predb.filename),
	 predb.nuked = IF(t.nuked > 0, t.nuked, predb.nuked),
	 predb.nukereason = IF(t.nuked > 0, t.nukereason, predb.nukereason),
	 predb.category = IF(predb.category is null, t.category, predb.category),
	 predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid),
	 predb.group_id = IF(g.id IS NOT NULL, g.id, predb.group_id)');

	// Add hashes
	echo $pdo->log->info("Adding predb_hashes entries");
	$pdo->queryDirect("INSERT IGNORE INTO predb_hashes (pre_id, hashes) (SELECT id, CONCAT_WS(',', MD5(title), MD5(MD5(title)), SHA1(title)) FROM predb)");

	// Re-add triggers on predb
	echo $pdo->log->info("Adding predb_hashes triggers");
	$pdo->exec("CREATE TRIGGER insert_hashes AFTER INSERT ON predb FOR EACH ROW BEGIN INSERT INTO predb_hashes (pre_id, hashes) VALUES (NEW.id, CONCAT_WS(',', MD5(NEW.title), MD5(MD5(NEW.title)), SHA1(NEW.title))); END;");
	$pdo->exec("CREATE TRIGGER update_hashes AFTER UPDATE ON predb FOR EACH ROW BEGIN IF NEW.title != OLD.title THEN UPDATE predb_hashes SET hashes = CONCAT_WS(',', MD5(NEW.title), MD5(MD5(NEW.title)), SHA1(NEW.title)) WHERE pre_id = OLD.id; END IF; END;");

	// Drop tmp_pre table
	$pdo->queryExec('DROP TABLE IF EXISTS tmp_pre');
} else {
	exit($pdo->log->error("\nThis script can export or import a predb dump file. You may use the full path, or a relative path.\n"
					. "For importing, the script insert new rows and update existing matched rows. For databases not on the local system, use remote, else use local.\n"
					. "For exporting, the path must be writeable by mysql, any existing file[predb_dump.csv] will be
					overwritten.\n\n"
					. "php dump_predb.php export /path/to/write/to                     ...: To export.\n"
					. "php dump_predb.php [remote | local] /path/to/filename           ...: To import.\n"));
}
