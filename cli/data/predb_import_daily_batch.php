<?php

/* TODO better tune the queries for performance, including using prepared statements and
   pre-fetching group_id and other data for faster inclusion in the main query.
*/

use nzedb\db\PreDb;
use nzedb\utility\Utility;

$config = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR .
		  'config.php';
if (!is_file($config)) {
	exit('Place this script in the cli/data folder of nZEDb.' . PHP_EOL);
}
require_once $config;
unset($config);

if (!Utility::isWin()) {
	$fullPath = DS;
	$paths    = preg_split('#/#', nZEDb_RES);
	foreach ($paths as $path) {
		if ($path !== '') {
			$fullPath .= $path . DS;
			if (!is_readable($fullPath) || !is_executable($fullPath)) {
				exit('The (' . $fullPath . ') folder must be readable and executable by all.' .
					 PHP_EOL);
			}
		}
	}
	unset($fullPath, $paths, $path);
}

if (!is_writable(nZEDb_RES)) {
	exit('The (' . nZEDb_RES . ') folder must be writable.' . PHP_EOL);
}

$progress = rw_progress(settings_array());

if (!isset($argv[1]) || !is_numeric($argv[1]) && $argv[1] != 'progress' || !isset($argv[2]) ||
	!in_array($argv[2], ['local', 'remote']) || !isset($argv[3]) ||
	!in_array($argv[3], ['true', 'false'])
) {
	exit('This script quickly imports the daily PreDB dumps.' . PHP_EOL .
		 'Argument 1: Enter the unix time of the patch to start at.' . PHP_EOL .
		 'You can find the unix time in the file name of the patch, it\'s the long number.' .
		 PHP_EOL .
		 'You can put in 0 to import all the daily PreDB dumps.' . PHP_EOL .
		 'You can put in progress to track progress of the imports and only import newer ones.' .
		 PHP_EOL .
		 'Argument 2: If your MySQL server is local, type local else type remote.' . PHP_EOL .
		 'Argument 3: Show output of dump_predb.php or not, true | false' . PHP_EOL
	);
}
$fileName         = '_predb_dump.csv.gz';
$innerUrl         = 'fb2pffwwriruyco';
$baseUrl          = 'https://www.dropbox.com/sh/' . $innerUrl;
$folderUrl['url'] = $baseUrl . '/AACy9Egno_v2kcziVHuvWbbxa';

$result = nzedb\utility\Utility::getUrl($folderUrl);

if (!$result) {
	exit('Error connecting to dropbox.com, try again later?' . PHP_EOL);
}

$result = preg_match_all('/<a href="https:\/\/www.dropbox.com\/sh\/' . $innerUrl . '\/(\S+\/\d+' .
						 $fileName . '\?dl=0)"/',
						 $result,
						 $all_matches);
if ($result) {
	exec('clear');
	$all_matches = array_unique($all_matches[1]);
	$total       = count($all_matches);
	$pdo         = new \nzedb\db\Settings();

	if ($argv[1] != 'progress') {
		$progress['last'] = !is_numeric($argv[1]) ? time() : $argv[1];
	}

	$pdo->queryExec('DROP TABLE IF EXISTS tmp_pre');
	$pdo->queryExec('CREATE TABLE tmp_pre LIKE predb');

	// Drop id as it is not needed and incurs overhead creating each id.
	$pdo->queryExec('ALTER TABLE tmp_pre DROP COLUMN id');

	// Add a column for the group's name which is included instead of the group_id, which may be
	// different between individual databases
	$pdo->queryExec('ALTER TABLE tmp_pre ADD COLUMN groupname VARCHAR (255)');

	// Drop indexes on tmp_pre
	$pdo->queryExec('ALTER TABLE tmp_pre DROP INDEX `ix_predb_nfo`, DROP INDEX `ix_predb_predate`, DROP INDEX `ix_predb_source`, DROP INDEX `ix_predb_title`, DROP INDEX `ix_predb_requestid`');

	foreach ($all_matches as $matches) {
		if (preg_match('#^(.+)/(\d+)_#', $matches, $match)) {
			$timematch = -1 + $progress['last'];

			// Skip patches the user does not want.
			if ($match[2] < $timematch) {
				echo 'Skipping dump ' . $match[2] . ', as your minimum unix time argument is ' .
					 $timematch . PHP_EOL;
				--$total;
				continue;
			}

			// Download the dump.
			$file['url'] = $baseUrl . '/' . $match[1] . '/' . $match[2] . $fileName . '?dl=1';
			$dump        = nzedb\utility\Utility::getUrl($file);

			if (!$dump) {
				echo 'Error downloading dump ' . $match[2] . ' you can try manually importing it.' .
					 PHP_EOL;
				continue;
			}

			// Make sure we didn't get a HTML page.
			if (strlen($dump) < 5000 && strpos($dump, '<!DOCTYPE html>') !== false) {
				echo 'The dump file ' . $match[2] . ' might be missing from dropbox.' . PHP_EOL;
				continue;
			}

			// Decompress.
			$dump = gzdecode($dump);

			if (!$dump) {
				echo 'Error decompressing dump ' . $match[2] . '.' . PHP_EOL;
				continue;
			}

			// Store the dump.
			$dumpFile = nZEDb_RES . $match[2] . '_predb_dump.csv';
			$fetched  = file_put_contents($dumpFile, $dump);
			if (!$fetched) {
				echo 'Error storing dump file ' . $match[2] . ' in (' . nZEDb_RES . ').' . PHP_EOL;
				continue;
			}

			// Make sure it's readable by all.
			chmod($dumpFile, 0777);
			$local   = strtolower($argv[2]) == 'local' ? true : false;
			$verbose = $argv[3] == true ? true : false;
			importDump($dumpFile, $local, $verbose);

			// Delete the dump.
			//			unlink($dumpFile);

			$progress = rw_progress(settings_array($match[2] + 1, $progress), false);
			echo 'Successfully imported PreDB dump ' . $match[2] . ' ' . (--$total) .
				 ' dumps remaining to import.' . PHP_EOL;
		}
	}

	// Drop tmp_pre table
	$pdo->queryExec('DROP TABLE IF EXISTS tmp_pre');
}

function settings_array($last = null, $settings = null)
{
	if (is_null($settings)) {
		$settings['last'] = 0;
	}

	if (!is_null($last)) {
		$settings['last'] = $last;
	}

	return $settings;
}

function rw_progress($settings, $read = true)
{
	if (!$read || !is_file(__DIR__ . DS . 'predb_progress.txt')) {
		file_put_contents(__DIR__ . DS . 'predb_progress.txt', base64_encode(serialize($settings)));
	} else {
		$settings = unserialize(base64_decode(file_get_contents(__DIR__ . DS .
																'predb_progress.txt')));
	}

	return $settings;
}

// This function duplicates how dump_predb works but does not drop the hashes/triggers as recreating
// potentially millions for the small addition that dailies add, isn't worth it.
function importDump($path, $local, $verbose = true, $table = 'predb')
{
	global $pdo;

	// Create temp table to allow updating
	if ($verbose) {
		echo $pdo->log->info("Creating temporary table");
	}

	// TRuncate to clear any old data
	$pdo->queryDirect("TRUNCATE TABLE tmp_pre");

	// Import file into tmp_pre
	$sqlLoad = sprintf(
		"LOAD DATA %s INFILE '%s' IGNORE INTO TABLE tmp_pre FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);",
		($local === false ? 'LOCAL' : ''),
		$path
	);
	if (nZEDb_DEBUG) {
		echo $pdo->log->header($sqlLoad);
	}
	$pdo->queryDirect($sqlLoad);

	// Remove any titles where length <=8
	if ($verbose) {
		echo $pdo->log->info("Deleting any records where title <=8 from Temporary Table");
	}
	$pdo->queryDirect("DELETE FROM tmp_pre WHERE LENGTH(title) <= 8");

	// Add any groups that do not currently exist
	$sqlAddGroups = <<<SQL_ADD_GROUPS
INSERT IGNORE INTO groups (`name`, description)
	SELECT groupname, 'Added by predb import script'
	FROM tmp_pre AS t LEFT JOIN groups AS g ON t.`groupname` = g.`name`
	WHERE t.`groupname` IS NOT NULL AND g.`name` IS NULL
	GROUP BY groupname;
SQL_ADD_GROUPS;

	$pdo->queryDirect($sqlAddGroups);

	// Fill the group_id
	$pdo->queryDirect("UPDATE tmp_pre AS t SET group_id = (SELECT id FROM groups WHERE name = t.groupname) WHERE groupname IS NOT NULL");

	// Insert and update table
	$sqlInsert = <<<SQL_INSERT
INSERT INTO $table (title, nfo, size, files, filename, nuked, nukereason, category, predate, SOURCE, requestid, group_id)
  SELECT t.title, t.nfo, t.size, t.files, t.filename, t.nuked, t.nukereason, t.category, t.predate, t.source, t.requestid, t.group_id
    FROM tmp_pre AS t
  ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo IS NULL, t.nfo, predb.nfo),
	  predb.size = IF(predb.size IS NULL, t.size, predb.size),
	  predb.files = IF(predb.files IS NULL, t.files, predb.files),
	  predb.filename = IF(predb.filename = '', t.filename, predb.filename),
	  predb.nuked = IF(t.nuked > 0, t.nuked, predb.nuked),
	  predb.nukereason = IF(t.nuked > 0, t.nukereason, predb.nukereason),
	  predb.category = IF(predb.category IS NULL, t.category, predb.category),
	  predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid),
	  predb.group_id = IF(predb.group_id = 0, t.group_id, predb.group_id);
SQL_INSERT;

	echo $pdo->log->info("Inserting records from temporary table into $table");
	if (nZEDb_DEBUG) {
		echo $pdo->log->primary($sqlInsert);
	}
	if ($pdo->queryDirect($sqlInsert) === false) {
		echo "FAILED\n";
	}
}
