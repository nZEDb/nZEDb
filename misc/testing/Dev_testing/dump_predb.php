<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;

if (isset($argv[2]))
{
	if (!preg_match('/^\//', $argv[2]))
		$path = getcwd() . '/' . $argv[2];
	else
		$path = $argv[2];
}

if (isset($argv[1]) && $argv[1] == 'export' && isset($argv[2]))
{
	if (!preg_match('/\.csv$/', $path))
		$path = dirname($path).'/'.basename($path).'/prebd_dump.csv';
	else
		$path = $path;
	if (!preg_match('/^\//', $path))
		$path = getcwd() . '/' . $path;

	if (file_exists($path) && is_file($path))
		unlink($path);
	if (isset($argv[3]))
		$table = $argv[3];
	else
		$table = 'predb';
	$db = new DB();
	$db->queryDirect("SELECT title, nfo, size, category, predate, adddate, source, md5, requestid, groupid INTO OUTFILE '".$path."' FROM ".$table);
}
else if (isset($argv[1]) && ($argv[1] == 'local' || $argv[1] == 'remote') && isset($argv[2]) && is_file($argv[2]))
{
	if (!preg_match('/^\//', $path))
		$path = require_once getcwd() . '/' . $argv[2];
	if (isset($argv[3]))
		$table = $argv[3];
	else
		$table = 'predb';

	$db = new DB();

	// Create temp table to allow updating
	$db->queryExec('DROP TABLE IF EXISTS tmp_pre');
	$db->queryExec('CREATE TABLE tmp_pre LIKE predb');

	// Drop indexes on tmp_pre
	$db->queryExec('ALTER TABLE tmp_pre DROP INDEX `ix_predb_md5`, DROP INDEX `ix_predb_nfo`, DROP INDEX `ix_predb_predate`, DROP INDEX `ix_predb_adddate`, DROP INDEX `ix_predb_source`, DROP INDEX `ix_predb_title`, DROP INDEX `ix_predb_requestid`');

	// Import file into tmp_pre
	if ($argv[1] == 'remote')
	{
		$db->queryDirect("LOAD DATA LOCAL INFILE '".$path."' IGNORE into table tmp_pre (title, nfo, size, category, predate, adddate, source, md5, requestid, groupid)");
	}
	else
	{
		$db->queryDirect("LOAD DATA INFILE '".$path."' IGNORE into table tmp_pre (title, nfo, size, category, predate, adddate, source, md5, requestid, groupid)");
	}

	// Insert and update table
	$db->queryDirect('INSERT INTO '.$table.' (title, nfo, size, category, predate, adddate, source, md5, requestid, groupid) SELECT t.title, t.nfo, t.size, t.category, t.predate, t.adddate, t.source, t.md5, t.requestid, t.groupid FROM tmp_pre t ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo is null, t.nfo, predb.nfo), predb.size = IF(predb.size is null, t.size, predb.size), predb.category = IF(predb.category is null, t.category, predb.category), predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid), predb.groupid = IF(predb.groupid = 0, t.groupid, predb.groupid)');

	// Drop tmp_pre table
	$db->queryExec('DROP TABLE IF EXISTS tmp_pre');
}
else
	exit($c->error("\nThis script can export or import a predb dump file. You may use the full path, or a relative path.".
		"\nFor importing, the script insert new rows and update existing matched rows. For databases not on the local system, use remote, else use local.".
		"\nFor exporting, the path must be writeable by mysql, any existing file[prebd_dump.csv] will be overwritten.\nTo export:\nphp dump_predb.php export /path/to/write/to\n\nTo import:\nphp dump_predb.php [remote | local] /path/to/filename"));
