<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

$db = new DB();
printf("This script will show all Active Groups. To sort by first_record_postdate - newest first, run active_groups.php true\n");
if (isset($argv[1]) && $argv[1] === "true")
{
	$mask = "\033[1;33m%-50.50s %5.5s %22.22s\n";
	if ($rels = $db->query(sprintf("select name, backfill_target, first_record_postdate from groups where active = 1 order by first_record_postdate DESC")))
	{
	        foreach ($rels as $rel)
        	{
                	printf($mask, $rel['name'], $rel['backfill_target'], $rel['first_record_postdate']);
	        }
	}
	printf("\033[0m");
}
else
{
        $mask = "\033[1;33m%-50.50s %5.5s %22.22s\n";
        if ($rels = $db->query(sprintf("select name, backfill_target, first_record_postdate from groups where active = 1")))
        {
                foreach ($rels as $rel)
                {
                        printf($mask, $rel['name'], $rel['backfill_target'], $rel['first_record_postdate']);
                }
        }
        printf("\033[0m");
}
?>

