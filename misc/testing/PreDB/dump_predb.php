<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

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
	echo  $pdo->log->header("SELECT title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, g.name FROM " . $table . " p LEFT OUTER JOIN groups g ON p.group_id = g.id INTO OUTFILE '" . $path . "' FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n';\n");
	$pdo->queryExec("SELECT title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, g.name FROM " . $table . " p LEFT OUTER JOIN groups g ON p.group_id = g.id INTO OUTFILE '" . $path . "' FIELDS TERMINATED BY '\t\t' LINES TERMINATED BY '\r\n'");
} else if (isset($argv[1]) && ($argv[1] == 'local' || $argv[1] == 'remote') && isset($argv[2]) && is_file($argv[2])) {
	if (!preg_match('/^\//', $path)) {
		$path = require_once getcwd() . '/' . $argv[2];
	}
	if (isset($argv[3])) {
		$table = $argv[3];
	} else {
		$table = 'predb';
	}

	// Truncate predb_imports to clear any old data
	$pdo->queryExec("TRUNCATE TABLE predb_imports");

	// Import file into predb_imports
	if ($argv[1] == 'remote') {
		echo  $pdo->log->header("LOAD DATA LOCAL INFILE '" . $path . "' IGNORE into table predb_imports FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);");
		$pdo->queryExec("LOAD DATA LOCAL INFILE '" . $path . "' IGNORE into table predb_imports FIELDS TERMINATED BY '\t\t' OPTIONALLY ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname)");
	} else {
		echo  $pdo->log->header("LOAD DATA INFILE '" . $path . "' IGNORE into table predb_imports FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);");
		$pdo->queryExec("LOAD DATA INFILE '" . $path . "' IGNORE into table predb_imports FIELDS TERMINATED BY '\t\t' OPTIONALLY ENCLOSED BY \"'\" LINES TERMINATED BY '\r\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname)");
	}

	// Remove any titles where length <=8
	echo $pdo->log->info("Deleting any records where title <=8 from Temporary Table");
	$pdo->queryExec("DELETE FROM predb_imports WHERE LENGTH(title) <= 8");

	// Add any groups that do not currently exist
	$sqlAddGroups = <<<SQL_ADD_GROUPS
INSERT IGNORE INTO groups (`name`, description)
	SELECT groupname, 'Added by predb import script'
	FROM predb_imports AS t LEFT JOIN groups AS g ON t.`groupname` = g.`name`
	WHERE t.`groupname` IS NOT NULL AND g.`name` IS NULL
	GROUP BY groupname;
SQL_ADD_GROUPS;
	$pdo->queryExec($sqlAddGroups);

	// Drop triggers on predb
	echo $pdo->log->info("Dropping predb_hashes triggers");
	$pdo->queryExec("DROP TRIGGER IF EXISTS insert_hashes");
	$pdo->queryExec("DROP TRIGGER IF EXISTS update_hashes");
	$pdo->queryExec("DROP TRIGGER IF EXISTS delete_hashes");

	// Insert and update table
	$sqlInsert = <<<SQL_INSERT
INSERT INTO $table (title, nfo, size, files, filename, nuked, nukereason, category, predate, SOURCE, requestid, group_id)
  SELECT t.title, t.nfo, t.size, t.files, t.filename, t.nuked, t.nukereason, t.category, t.predate, t.source, t.requestid, IF(g.id IS NOT NULL, g.id, 0)
    FROM predb_imports AS t
	LEFT OUTER JOIN groups g ON t.groupname = g.name ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo IS NULL, t.nfo, predb.nfo),
	  predb.size = IF(predb.size IS NULL, t.size, predb.size),
	  predb.files = IF(predb.files IS NULL, t.files, predb.files),
	  predb.filename = IF(predb.filename = '', t.filename, predb.filename),
	  predb.nuked = IF(t.nuked > 0, t.nuked, predb.nuked),
	  predb.nukereason = IF(t.nuked > 0, t.nukereason, predb.nukereason),
	  predb.category = IF(predb.category IS NULL, t.category, predb.category),
	  predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid),
	  predb.group_id = IF(g.id IS NOT NULL, g.id, 0);
SQL_INSERT;
	echo $pdo->log->primary($sqlInsert);
	$pdo->queryExec($sqlInsert);

	// Add hashes                                                                                                                                                                               g
	echo $pdo->log->info("Adding predb_hashes entries");
	echo $pdo->log->info("Stage 1: UNHEX(MD5(TITLE))");
	$pdo->queryExec("INSERT IGNORE INTO predb_hashes (hash, pre_id) SELECT UNHEX(md5(title)), id from predb;");
	echo $pdo->log->info("Stage 1: UNHEX(MD5(MD5(TITLE)))");
	$pdo->queryExec("INSERT IGNORE INTO predb_hashes (hash, pre_id) SELECT UNHEX(md5(md5(title))), id from predb;");
	echo $pdo->log->info("Stage 1: UNHEX(SHA1(TITLE))");
	$pdo->queryExec("INSERT IGNORE INTO predb_hashes (hash, pre_id) SELECT UNHEX(sha1(title)), id from predb;");

	// Re-add triggers on predb
	echo $pdo->log->info("Adding predb_hashes triggers");
	$pdo->queryExec("CREATE TRIGGER insert_hashes AFTER INSERT ON predb FOR EACH ROW BEGIN INSERT INTO predb_hashes (hash, pre_id) VALUES (UNHEX(MD5(NEW.title)), NEW.id), (UNHEX(MD5(MD5(NEW.title))), NEW.id), ( UNHEX(SHA1(NEW.title)), NEW.id); END;");
	$pdo->queryExec("CREATE TRIGGER update_hashes AFTER UPDATE ON predb FOR EACH ROW BEGIN IF NEW.title != OLD.title THEN DELETE FROM predb_hashes WHERE hash IN ( UNHEX(md5(OLD.title)), UNHEX(md5(md5(OLD.title))), UNHEX(sha1(OLD.title)) ) AND pre_id = OLD.id; INSERT INTO predb_hashes (hash, pre_id) VALUES ( UNHEX(MD5(NEW.title)), NEW.id ), ( UNHEX(MD5(MD5(NEW.title))), NEW.id ), ( UNHEX(SHA1(NEW.title)), NEW.id ); END IF; END;");
	$pdo->queryExec("CREATE TRIGGER delete_hashes BEGIN DELETE FROM predb_hashes WHERE hash IN ( UNHEX(md5(OLD.title)), UNHEX(md5(md5(OLD.title))), UNHEX(sha1(OLD.title)) ) AND pre_id = OLD.id; END;");

	$pdo->queryExec("TRUNCATE TABLE predb_imports");
} else {
	exit($pdo->log->error("\nThis script can export or import a predb dump file. You may use the full path, or a relative path.\n"
					. "For importing, the script insert new rows and update existing matched rows. For databases not on the local system, use remote, else use local.\n"
					. "For exporting, the path must be writeable by mysql, any existing file[predb_dump.csv] will be
					overwritten.\n\n"
					. "php dump_predb.php export /path/to/write/to                     ...: To export.\n"
					. "php dump_predb.php [remote | local] /path/to/filename           ...: To import.\n"));
}
