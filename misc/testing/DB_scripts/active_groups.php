<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

$db = new DB();
printf("\033[1;33mThis script will show all Active Groups.\nAn optional first argument of true/false is used to sort the display by first_record_postdate in descending order.\nAn optional second argument will limit the return to that number of groups.\nTo sort the active groups by first_record_postdate and display only 20 groups run:\n  php active_groups.php true 20\n\033[0m\n\n");
if (isset($argv[2]) && is_numeric($argv[2]) )
	$limit = "limit ".$argv[2];
else
	$limit = "";

$mask = "\033[1;33m%-50.50s %22.22s %22.22s %22.22s %22.22s\n";
printf($mask, "Group Name", "Backfilled Days", "Oldest Post", "Last Updated", "Headers Downloaded");
printf($mask, "==================================================", "======================", "======================", "======================", "======================");

if (isset($argv[1]) && $argv[1] === "true")
{
    if ($rels = $db->query(sprintf("select name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record as SIGNED)-CAST(first_record as SIGNED) as 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS Days from groups where active = 1 order by first_record_postdate DESC %s", $limit)))
    {
        foreach ($rels as $rel)
        {
            printf($mask, $rel['name'], $rel['backfill_target']."(".$rel['Days'].")", $rel['first_record_postdate'], $rel['last_updated'], $rel['headers downloaded']);
        }
    }
    printf("\033[0m");
}
else
{
	if ($rels = $db->query(sprintf("select name, backfill_target, first_record_postdate, last_updated, last_updated, CAST(last_record as SIGNED)-CAST(first_record as SIGNED) as 'headers downloaded', TIMESTAMPDIFF(DAY,first_record_postdate,NOW()) AS Days from groups where active = 1 order by first_record_postdate ASC %s", $limit)))
	{
		foreach ($rels as $rel)
		{
			printf($mask, $rel['name'], $rel['backfill_target']."(".$rel['Days'].")", $rel['first_record_postdate'], $rel['last_updated'], $rel['headers downloaded']);
		}
	}
	printf("\033[0m");
}
?>

