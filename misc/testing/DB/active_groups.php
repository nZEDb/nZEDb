<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'ColorCLI.php';

$db = new DB();
$c = new ColorCLI();
$count = $groups = 0;
if (!isset($argv[1])) {
	passthru("clear");
	exit($c->error("\nThis script will show all Active Groups. There is 1 required argument and 2 optional arguments.\nThe first argument of [date, releases] is used to sort the display by first_record_postdate or by the number of releases.\nThe second argument [ASC, DESC] sorts by ascending or descending.\nThe third argument will limit the return to that number of groups.\nTo sort the active groups by first_record_postdate and display only 20 groups run:\nphp active_groups.php date desc 20\n"));
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
if ($rels = $db->queryDirect("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups")) {
	foreach ($rels as $rel) {
		$count += $rel['headers downloaded'];
		$groups++;
	}
}

$active = $db->queryOneRow("SELECT COUNT(*) AS count FROM groups WHERE ACTIVE = 1");
printf($mask, "\nGroup Name => " . $active['count'] . "[" . $groups . "] (" . number_format($count) . " downloaded)", "Backfilled Days", "Oldest Post", "Last Updated", "Headers Downloaded", "Releases", "Renamed", "PreDB Matches");
printf($mask, "==================================================", "======================", "======================", "======================", "======================", "======================", "======================", "======================");

if ($rels = $db->queryDirect(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated,
										CAST(last_record as SIGNED)-CAST(first_record as SIGNED) AS 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days,
										COALESCE(rel.num, 0) AS num_releases,
										COALESCE(pre.num, 0) AS pre_matches,
										COALESCE(ren.num, 0) AS renamed FROM groups
										LEFT OUTER JOIN ( SELECT groupid, COUNT(id) AS num FROM releases GROUP BY groupid ) rel ON rel.groupid = groups.id
										LEFT OUTER JOIN ( SELECT groupid, COUNT(id) AS num FROM releases WHERE preid is not null GROUP BY groupid ) pre ON pre.groupid = groups.id
										LEFT OUTER JOIN ( SELECT groupid, COUNT(id) AS num FROM releases WHERE (bitwise & 1) = 1 GROUP BY groupid ) ren ON ren.groupid = groups.id
										WHERE active = 1 AND first_record_postdate %s %s %s", $order, $sort, $limit))) {
	foreach ($rels as $rel) {
		//var_dump($rel);
		$headers = number_format($rel['headers downloaded']);
		printf($mask, $rel['name'], $rel['backfill_target'] . "(" . $rel['days'] . ")", $rel['first_record_postdate'], $rel['last_updated'], $headers, $rel['num_releases'], $rel['renamed'] . "(" . floor($rel['renamed'] / $rel['num_releases'] * 100) . "%)", $rel['pre_matches'] . "(" . floor($rel['pre_matches'] / $rel['num_releases'] * 100) . "%)");
	}
}
