<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

//
//	This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.
//

if(isset($argv[1]) && $argv[1] == "true")
{
	
	$db = new DB;


	$rel = $db->query("update groups set backfill_target=0, first_record=0, first_record_postdate=null, last_record=0, last_record_postdate=null, last_updated=null");
	printf("Reseting all groups completed.\n");

	$arr = array("parts", "partrepair", "binaries", "collections");
	foreach ($arr as &$value) {
			$rel = $db->query("truncate table $value");
			printf("Truncating $value completed.\n");
	}
	unset($value);

	$db->query("delete from releases where nzbstatus = 0");
	echo $db->getAffectedRows()." releases had no nzb, deleted.";
}
else
{
	exit("This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.\nIf you are sure you want to run it, type php reset_truncate.php true\n");
}
?>

