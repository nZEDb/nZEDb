<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$db = new DB();
$count = $groups = 0;
passthru("clear");
printf("\033[1;33mThis script will show all Backfill Groups.\nAn optional first argument of true/false is used to sort the display by first_record_postdate in descending order.\nAn optional second argument will limit the return to that number of groups.\nTo sort the backfill groups by first_record_postdate and display only 20 groups run:\n  php backfill_groups.php true 20\n\033[0m\n\n");
$limit = "";
if (isset($argv[2]) && is_numeric($argv[2]))
	$limit = "limit ".$argv[2];
$mask = "\033[1;33m%-50.50s %22.22s %22.22s %22.22s %22.22s\n";
$groups = $db->queryOneRow("SELECT COUNT(*) AS count FROM groups WHERE backfill = 1 AND first_record IS NOT NULL AND first_record_postdate != '2000-01-01'");
if ($rels = $db->query("SELECT last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded FROM groups"))
{
	foreach ($rels as $rel)
	{
		$count += $rel['headers_downloaded'];
	}
}

printf($mask, "Group Name => ".$groups['count']."(".number_format($count)." downloaded)", "Backfilled Days", "Oldest Post", "Last Updated", "Headers Downloaded");
printf($mask, "==================================================", "======================", "======================", "======================", "======================");

if (isset($argv[1]) && $argv[1] === "true")
{
	if ($rels = $db->query(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded, TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups WHERE backfill = 1 AND first_record_postdate IS NOT NULL AND last_updated IS NOT NULL AND last_updated IS NOT NULL ORDER BY first_record_postdate DESC %s", $limit)))
	{
		foreach ($rels as $rel)
		{
			$headers = number_format($rel['headers_downloaded']);
			printf($mask, $rel['name'], $rel['backfill_target']."(".$rel['days'].")", $rel['first_record_postdate'], $rel['last_updated'], $headers);
		}
	}
}
else
{
	if ($rels = $db->query(sprintf("SELECT name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record AS SIGNED)-CAST(first_record AS SIGNED) AS headers_downloaded, TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS days FROM groups WHERE backfill = 1 AND first_record_postdate IS NOT NULL AND last_updated IS NOT NULL AND last_updated IS NOT NULL ORDER BY first_record_postdate ASC %s", $limit)))
	{
		foreach ($rels as $rel)
		{
			$headers = number_format($rel['headers_downloaded']);
			printf($mask, $rel['name'], $rel['backfill_target']."(".$rel['days'].")", $rel['first_record_postdate'], $rel['last_updated'], $headers);
		}
	}
}
?>
