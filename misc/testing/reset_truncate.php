<?php
require(dirname(__FILE__)."/../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

$db = new DB;


$rel = $db->query("update groups set backfill_target=0, first_record=0, first_record_postdate=null, last_record=0, last_record_postdate=null, last_updated=null");
printf("Reseting all groups completed.\n");

$arr = array("parts", "partrepair", "binaries", "collections");
foreach ($arr as &$value) {
        $rel = $db->query("truncate table $value");
        printf("Truncating $value completed.\n");
}
unset($value);

?>

