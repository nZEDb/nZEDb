<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$c = new ColorCLI();
$count = $groups = 0;
if (!isset($argv[1])) {
	passthru("clear");
	exit($c->error("\nThis script will show all Active Groups. There is 1 required argument and 2 optional arguments.\n"
			. "The first argument of [date, releases] is used to sort the display by first_record_postdate or by the number of releases.\n"
			. "The second argument [ASC, DESC] sorts by ascending or descending.\n"
			. "The third argument will limit the return to that number of groups.\n"
			. "To sort the active groups by first_record_postdate and display only 20 groups run:\n"
			. "php $argv[0] date desc 20\n"));
}
passthru("clear");
if (isset($argv[1]) && $argv[1] == "date") {
	$order = "order by first_record_postdate";
} else if (isset($argv[1]) && $argv[1] == "releases") {
	$order = "order by num_releases";
} else {
	$order = "";
}

if (isset($argv[2]) && ($argv[2] == "ASC" || $argv[2] == "asc")) {
	$sort = "ASC";
} else if (isset($argv[2]) && ($argv[2] == "DESC" || $argv[2] == "desc")) {
	$sort = "DESC";
} else {
	$sort = "";
}

if (isset($argv[3]) && is_numeric($argv[3])) {
	$limit = "LIMIT " . $argv[3];
} else if (isset($argv[2]) && is_numeric($argv[2])) {
	$limit = "LIMIT " . $argv[2];
} else {
	$limit = "";
}

$mask = $c->primary("%-50.50s %22.22s %22.22s %22.22s %22.22s %22.22s %22.22s %22.22s");
if ($rels = $pdo->queryDirect("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups")) {
	foreach ($rels as $rel) {
		$count += $rel['headers downloaded'];
		$groups++;
	}
}

$active = $pdo->queryOneRow("SELECT COUNT(*) AS count FROM groups WHERE ACTIVE = 1");
printf($mask, "\nGroup Name => " . $active['count'] . "[" . $groups . "] (" . number_format($count) . " downloaded)", "Backfilled Days", "Oldest Post", "Last Updated", "Headers Downloaded", "Releases", "Renamed", "PreDB Matches");
printf($mask, "==================================================", "======================", "======================", "======================", "======================", "======================", "======================", "======================");

if ($rels = $pdo->queryDirect(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated,
		CAST(last_record as SIGNED)-CAST(first_record as SIGNED) AS 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days,
		COALESCE(rel.num, 0) AS num_releases,
		COALESCE(pre.num, 0) AS pre_matches,
		COALESCE(ren.num, 0) AS renamed FROM groups
		LEFT OUTER JOIN ( SELECT group_id, COUNT(id) AS num FROM releases GROUP BY group_id ) rel ON rel.group_id = groups.id
		LEFT OUTER JOIN ( SELECT group_id, COUNT(id) AS num FROM releases WHERE preid > 0 GROUP BY group_id ) pre ON pre.group_id = groups.id
		LEFT OUTER JOIN ( SELECT group_id, COUNT(id) AS num FROM releases WHERE iscategorized = 1 GROUP BY group_id ) ren ON ren.group_id = groups.id
		WHERE active = 1 AND first_record_postdate %s %s %s", $order, $sort, $limit))) {
	foreach ($rels as $rel) {
		//var_dump($rel);
		$headers = number_format($rel['headers downloaded']);
		printf($mask, $rel['name'], $rel['backfill_target'] . "(" . $rel['days'] . ")", $rel['first_record_postdate'], $rel['last_updated'], $headers, number_format($rel['num_releases']), $rel['num_releases'] == 0 ? number_format($rel['num_releases']) : number_format($rel['renamed']) . "(" . floor($rel['renamed'] / $rel['num_releases'] * 100) . "%)", $rel['num_releases'] == 0 ? number_format($rel['num_releases']) : number_format($rel['pre_matches']) . "(" . floor($rel['pre_matches'] / $rel['num_releases'] * 100) . "%)");
	}
}
