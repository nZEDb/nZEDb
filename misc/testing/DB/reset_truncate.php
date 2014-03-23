<?php

// This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.
require_once dirname(__FILE__) . '/../../../www/config.php';

$c = new ColorCLI();

if (isset($argv[1]) && ($argv[1] == "true" || $argv[1] == "drop")) {
	$db = new DB();
	$db->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, last_updated = NULL");
	echo $c->primary("Reseting all groups completed.");

	$arr = array("parts", "partrepair", "binaries", "collections", "nzbs");
	foreach ($arr as &$value) {
		$rel = $db->queryExec("TRUNCATE TABLE $value");
		if ($rel !== false) {
			echo $c->primary("Truncating ${value} completed.");
		}
	}
	unset($value);

	$s = new Sites();
	$site = $s->get();
	$tablepergroup = (!empty($site->tablepergroup)) ? $site->tablepergroup : 0;

	if ($tablepergroup == 1) {
		if ($db->dbsystem == 'mysql') {
			$sql = 'SHOW table status';
		} else {
			$sql = "SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'r'";
		}

		$tables = $db->query($sql);
		foreach ($tables as $row) {
			if ($db->dbsystem == 'mysql') {
				$tbl = $row['name'];
			} else {
				$tbl = $row['relname'];
			}
			if (preg_match('/collections_\d+/', $tbl) || preg_match('/binaries_\d+/', $tbl) || preg_match('/parts_\d+/', $tbl) || preg_match('/partrepair_\d+/', $tbl) || preg_match('/\d+_collections/', $tbl) || preg_match('/\d+_binaries/', $tbl) || preg_match('/\d+_parts/', $tbl) || preg_match('/\d+_partrepair_\d+/', $tbl)) {
				if ($argv[1] == "drop") {
					$rel = $db->queryDirect(sprintf('DROP TABLE %s', $tbl));
					if ($rel !== false) {
						echo $c->primary("Dropping ${tbl} completed.");
					}
				} else {
					$rel = $db->queryDirect(sprintf('TRUNCATE TABLE %s', $tbl));
					if ($rel !== false) {
						echo $c->primary("Truncating ${tbl} completed.");
					}
				}
			}
		}
	}

	$delcount = $db->queryDirect("DELETE FROM releases WHERE nzbstatus = 0");
	echo $c->primary($delcount->rowCount() . " releases had no nzb, deleted.");
} else {
	exit($c->error("\nThis script removes releases with no NZBs, resets all groups, truncates or drops(tpg) \n"
			. "article tables. All other releases are left alone.\n"
			. "php $argv[0] [true, drop]   ...: To reset all groups and truncate/drop the tables.\n"));
}
