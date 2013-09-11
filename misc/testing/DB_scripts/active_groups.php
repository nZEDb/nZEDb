<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

$db = new DB();
$count = $groups = 0;
if (!isset($argv[1]))
{
	passthru("clear");
	printf("\033[1;33mThis script will show all Active Groups. There is 1 required argument and 2 optional arguments.\nThe first argument of [date, releases] is used to sort the display by first_record_postdate or by the number of releases.\nThe second argument [ASC, DESC] sorts by ascending or descending.\nThe third argument will limit the return to that number of groups.\nTo sort the active groups by first_record_postdate and display only 20 groups run:\n  php active_groups.php date desc 20\n\033[0m\n\n");
	exit();
}

if (isset($argv[1]) && $argv[1] == "date")
	$order = "order by first_record_postdate";
elseif (isset($argv[1]) && $argv[1] == "releases")
	$order = "order by num_releases";
else
	$order = "";

if (isset($argv[2]) && ($argv[2] == "ASC" || $argv[2] == "asc"))
	$sort = "ASC";
elseif (isset($argv[2]) && ($argv[2] == "DESC" || $argv[2] == "desc"))
	$sort = "DESC";
else
	$sort = "";

if (isset($argv[3]) && is_numeric($argv[3]))
	$limit = "LIMIT ".$argv[3];
elseif (isset($argv[2]) && is_numeric($argv[2]))
	$limit = "LIMIT ".$argv[2];
else
	$limit = "";

$mask = "\033[1;33m%-50.50s %22.22s %22.22s %22.22s %22.22s %22.22s\n";
if ($rels = $db->query("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups"))
{
	foreach ($rels as $rel)
	{
		$count += $rel['headers downloaded'];
		$groups++;
	}
}

printf($mask, "\nGroup Name => ".$groups."(".number_format($count)." downloaded)", "Backfilled Days", "Oldest Post", "Last Updated", "Headers Downloaded", "Releases");
printf($mask, "==================================================", "======================", "======================", "======================", "======================", "======================");

if ($rels = $db->query(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated, CAST(last_record as SIGNED)-CAST(first_record as SIGNED) AS 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days, COALESCE(rel.num, 0) AS num_releases FROM groups LEFT OUTER JOIN ( SELECT groupid, COUNT(id) AS num FROM releases GROUP BY groupid ) rel ON rel.groupid = groups.id WHERE active = 1 AND first_record_postdate %s %s %s", $order, $sort, $limit)))
{
	foreach ($rels as $rel)
	{
		//var_dump($rel);
		$headers = number_format($rel['headers downloaded']);
		printf($mask, $rel['name'], $rel['backfill_target']."(".$rel['days'].")", $rel['first_record_postdate'], $rel['last_updated'], $headers, $rel['num_releases']);
	}
}
echo "\033[0m\n";
?>
