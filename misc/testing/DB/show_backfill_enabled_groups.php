<?php
require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$count = $groups = 0;
$cli = new \ColorCLI();

echo $cli->header("This script will show all Backfill Groups.\n"
	. "An optional first argument of ASC/DESC is used to sort the display by first_record_postdate in ascending/descending order.\n"
	. "An optional second argument will limit the return to that number of groups.\n\n"
	. "php $argv[0] true 20    ...: To sort the backfill groups by first_record_postdate and display only 20 groups.\n");

$limit = "";
if (isset($argv[2]) && is_numeric($argv[2])) {
	$limit = "limit " . $argv[2];
} else if (isset($argv[1]) && is_numeric($argv[1])) {
	$limit = "limit " . $argv[1];
}

$mask = $cli->primary("%-50.50s %22.22s %22.22s %22.22s %22.22s");
$mask1 = $cli->header("%-50.50s %22.22s %22.22s %22.22s %22.22s");
$groups = $pdo->queryOneRow("SELECT COUNT(*) AS count FROM groups WHERE backfill = 1 AND first_record IS NOT NULL");
if ($rels = $pdo->query("SELECT last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded FROM groups")) {
	foreach ($rels as $rel) {
		$count += $rel['headers_downloaded'];
	}
}

printf($mask1, "Group Name => " . $groups['count'] . "(" . number_format($count) . " downloaded)", "Backfilled Days", "Oldest Post", "Last Updated", "Headers Downloaded");
printf($mask1, "==================================================", "======================", "======================", "======================", "======================");

if (isset($argv[1]) && ($argv[1] === "desc" || $argv[1] === "DESC")) {
	if ($rels = $pdo->query(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, "
			. "CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded, "
			. "TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups "
			. "WHERE backfill = 1 AND first_record_postdate IS NOT NULL AND last_updated IS NOT NULL "
			. "AND last_updated IS NOT NULL ORDER BY first_record_postdate DESC %s", $limit))) {
		foreach ($rels as $rel) {
			$headers = number_format($rel['headers_downloaded']);
			printf($mask, $rel['name'], $rel['backfill_target'] . "(" . $rel['days'] . ")", $rel['first_record_postdate'], $rel['last_updated'], $headers);
		}
	}
} else if (isset($argv[1]) && ($argv[1] === "asc" || $argv[1] === "ASC")) {
	if ($rels = $pdo->query(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, "
			. "CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded, "
			. "TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups "
			. "WHERE backfill = 1 AND first_record_postdate IS NOT NULL AND last_updated IS NOT NULL "
			. "AND last_updated IS NOT NULL ORDER BY first_record_postdate ASC %s", $limit))) {
		foreach ($rels as $rel) {
			$headers = number_format($rel['headers_downloaded']);
			printf($mask, $rel['name'], $rel['backfill_target'] . "(" . $rel['days'] . ")", $rel['first_record_postdate'], $rel['last_updated'], $headers);
		}
	}
} else {
	if ($rels = $pdo->query(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, "
			. "CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded, "
			. "TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups "
			. "WHERE backfill = 1 AND first_record_postdate IS NOT NULL AND last_updated IS NOT NULL AND "
			. "last_updated IS NOT NULL %s", $limit))) {
		foreach ($rels as $rel) {
			$headers = number_format($rel['headers_downloaded']);
			printf($mask, $rel['name'], $rel['backfill_target'] . "(" . $rel['days'] . ")", $rel['first_record_postdate'], $rel['last_updated'], $headers);
		}
	}
}
echo "\n";
