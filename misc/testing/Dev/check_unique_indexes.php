<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
$c = new ColorCLI();

if (!isset($argv[1])) {
	if ($argv[1] !== 'test' || $argv[1] !== 'alter') {
		exit($c->error("\nThis script will scan mysql.sql for all UNIQUE INDEXES.\n"
						. "It will verify that you have them. If you do not, you can choose to run manually or allow the script to run them.\n\n"
						. "php $argv[0] test      ...: To verify all unique indexes.\n"
						. "php $argv[0] alter     ...: To add missing unique indexes.\n"));
	}
}

// Set for Session
$db = new DB();
if ($argv[1] === 'alter') {
	$db->queryExec("SET SESSION old_alter_table = 1");
}

function run_query($query, $test)
{
	$c = new ColorCLI();
	if ($test === 'alter') {
		$db = new DB();
		try {
			$qry = $db->prepare($query);
			$qry->execute();
			echo $c->alternateOver('SUCCESS: ') . $c->primary($query);
		} catch (PDOException $e) {
			if ($e->errorInfo[1] == 1061) {
				// Duplicate key exists
				echo $c->alternateOver('SKIPPED Index name exists: ') . $c->primary($query);
			} else {
				echo $c->alternateOver('FAILED: ') . $c->primary($query);
			}
		}
	} else {
		echo $c->header($query);
	}
}

$match = '';
$path = nZEDb_ROOT . DS . 'db' . DS . 'mysql.sql';
$handle = fopen($path, "r");
if ($handle) {
	while (($line = fgets($handle)) !== false) {
		if (preg_match('/(CREATE UNIQUE INDEX) (.+) ON (.+) ?\((.+)\);/i', $line, $match)) {
			$columns = explode(',', $match[4]);
			foreach ($columns as $column) {
				$check = $db->queryOneRow("SHOW INDEXES IN " . trim($match[3]) . " WHERE non_unique = 0 AND column_name = '" . trim($column) . "'");
				if (!isset($check['key_name'])) {
					if (trim($match[3]) === 'collections') {
						$tables = $db->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/collections_\d+/', $tbl)) {
								$check_collections = $db->queryOneRow("SHOW INDEXES IN " . trim($tbl) . " WHERE non_unique = 0 AND column_name = '" . trim($column) . "'");
								if (!isset($check_collections['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT $match[2] UNIQUE (${match[4]})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match[3]) . " ADD CONSTRAINT " . trim($match[2]) ." UNIQUE (${match[4]})";
						run_query($qry, $argv[1]);
					} else if (trim($match[3]) === 'binaries') {
						$tables = $db->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/binaries_\d+/', $tbl)) {
								$checkBinaries = $db->queryOneRow("SHOW INDEXES IN " . trim($tbl) . " WHERE non_unique = 0 AND column_name = '" . trim($column) . "'");
								if (!isset($checkBinaries['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT $match[2] UNIQUE (${match[4]})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match[3]) . " ADD CONSTRAINT " . trim($match[2]) ." UNIQUE (${match[4]})";
						run_query($qry, $argv[1]);
					} else if (trim($match[3]) === 'parts') {
						$tables = $db->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/parts_\d+/', $tbl)) {
								$checkParts = $db->queryOneRow("SHOW INDEXES IN " . trim($tbl) . " WHERE non_unique = 0 AND column_name = '" . trim($column) . "'");
								if (!isset($checkParts['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT $match[2] UNIQUE (${match[4]})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match[3]) . " ADD CONSTRAINT " . trim($match[2]) ." UNIQUE (${match[4]})";
						run_query($qry, $argv[1]);
					} else if (trim($match[3]) === 'partrepair') {
						$tables = $db->query("SHOW TABLES");
						foreach ($tables as $row) {
							$tbl = $row['tables_in_' . DB_NAME];
							if (preg_match('/partrepair_\d+/', $tbl)) {
								$checkPartRepair = $db->queryOneRow("SHOW INDEXES IN " . trim($tbl) . " WHERE non_unique = 0 AND column_name = '" . trim($column) . "'");
								if (!isset($checkPartRepair['key_name'])) {
									$qry = "ALTER IGNORE TABLE ${tbl} ADD CONSTRAINT $match[2] UNIQUE (${match[4]})";
									run_query($qry, $argv[1]);
								}
							}
						}
						$qry = "ALTER IGNORE TABLE " . trim($match[3]) . " ADD CONSTRAINT " . trim($match[2]) ." UNIQUE (${match[4]})";
						run_query($qry, $argv[1]);
					} else {
						$qry = "ALTER IGNORE TABLE " . trim($match[3]) . " ADD CONSTRAINT " . trim($match[2]) ." UNIQUE (${match[4]})";
						run_query($qry, $argv[1]);
					}
				} else {
					echo $c->primary("A Unique Index exists for " . trim($match[3]) . " on " . trim($match[4]));
				}
			}
		}
	}
} else {
	echo $c->error("\nCan not open mysql.sql.");
}
