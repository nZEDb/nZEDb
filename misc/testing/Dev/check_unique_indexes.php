<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (!isset($argv[1])) {
	if ($argv[1] !== 'test' || $argv[1] !== 'alter') {
		exit($pdo->log->error("\nThis script will scan mysql-ddl.sql for all UNIQUE INDEXES.\n"
						. "It will verify that you have them. If you do not, you can choose to run manually or allow the script to run them.\n\n"
						. "php $argv[0] test      ...: To verify all unique indexes.\n"
						. "php $argv[0] alter     ...: To add missing unique indexes.\n"));
	}
}

// Set for Session
if (isset($argv[1]) && $argv[1] === 'alter') {
	$pdo->queryExec("SET SESSION old_alter_table = 1");
}

function run_query($query, $test)
{
	global $pdo;

	if ($test === 'alter') {
		try {
			$qry = $pdo->prepare($query);
			$qry->execute();
			echo $pdo->log->alternateOver('SUCCESS: ') . $pdo->log->primary($query);
		} catch (PDOException $e) {
			if ($e->errorInfo[1] == 1061) {
				// Duplicate key exists
				echo $pdo->log->alternateOver('SKIPPED Index name exists: ') . $pdo->log->primary($query);
			} else {
				echo $pdo->log->alternateOver('FAILED: ') . $pdo->log->primary($query);
			}
		}
	} else {
		echo $pdo->log->header($query);
	}
}

$path = nZEDb_RES . 'db' . DS . 'schema' . DS .'mysql-ddl.sql';
$handle = fopen($path, "r");
if ($handle) {
	while (($line = fgets($handle)) !== false) {
		if (preg_match('/(?P<statement>CREATE UNIQUE INDEX)\s+(?P<index>[\w-]+)\s+ON\s+(?P<table>[\w-]+)\s*\((?P<column>[\w-]+(?:\s*\((?P<size>\d+)\))?)\);/i', $line, $match)) {
			$columns = explode(',', $match['column']);
			foreach ($columns as $column) {
				$check = $pdo->checkColumnIndex($match['table'], $column);
				if (!isset($check['key_name'])) {
					if (trim($match['table']) === 'collections') {
						$tables = $pdo->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/collections_\d+/', $tbl)) {
								$check = $pdo->checkColumnIndex($tbl, $column);
								if (!isset($check_collections['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT {$match['index']} UNIQUE (${match['column']})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match['table']) . " ADD CONSTRAINT " . trim($match['index']) ." UNIQUE (${match['column']})";
						run_query($qry, $argv[1]);
					} else if (trim($match['table']) === 'binaries') {
						$tables = $pdo->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/binaries_\d+/', $tbl)) {
								$checkBinaries = $pdo->checkColumnIndex($tbl, $column);
								if (!isset($checkBinaries['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT {$match['index']} UNIQUE (${match['column']})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match['table']) . " ADD CONSTRAINT " . trim($match['index']) ." UNIQUE (${match['column']})";
						run_query($qry, $argv[1]);
					} else if (trim($match['table']) === 'parts') {
						$tables = $pdo->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/parts_\d+/', $tbl)) {
								$checkParts = $pdo->checkColumnIndex($tbl, $column);
								if (!isset($checkParts['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT {$match['index']} UNIQUE (${match['column']})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match['table']) . " ADD CONSTRAINT " . trim($match['index']) ." UNIQUE (${match['column']})";
						run_query($qry, $argv[1]);
					} else if (trim($match['table']) === 'partrepair') {
						$tables = $pdo->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/partrepair_\d+/', $tbl)) {
								$checkPartRepair = $pdo->checkColumnIndex($tbl, $column);
								if (!isset($checkPartRepair['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT {$match['index']} UNIQUE (${match['column']})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match['table']) . " ADD CONSTRAINT " . trim($match['index']) ." UNIQUE (${match['column']})";
						run_query($qry, $argv[1]);
					} else {
						$qry = "ALTER IGNORE TABLE " . trim($match['table']) . " ADD CONSTRAINT " . trim($match['index']) ." UNIQUE (${match['column']})";
						run_query($qry, $argv[1]);
					}
				} else {
					echo $pdo->log->primary("A Unique Index exists for " . trim($match['table']) . " on " . trim($match['column']));
				}
			}
		}
	}
} else {
	echo $pdo->log->error("\nCan not open mysql-ddl.sql.");
}
