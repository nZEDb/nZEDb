<?php

print "This is dangerous, comment this line out if you really want to reset your database\n";
exit();

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");

$db = new Db;

$rel = $db->query("DELETE FROM binaries");
$rel = $db->query("DELETE FROM parts");
$rel = $db->query("DELETE FROM partrepair");
$rel = $db->query("DELETE FROM releases");
$rel = $db->query("DELETE FROM releasefiles");
$rel = $db->query("DELETE FROM releasenfo");
$rel = $db->query("UPDATE groups SET first_record=0, first_record_postdate=NULL, last_record=0, last_record_postdate=NULL");

?>
