<?php
/* This script resets the relnamestatus to 1 on every release that doesn't have relnamestatus 3 or 7, so you can rerun fixReleaseNames.php miscsorter etc*/

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");

if (!isset($argv[1]))
	exit("This script resets the relnamestatus to 1 on every release that doesn't have relnamestatus 3 or 7, so you can rerun fixReleaseNames.php miscsorter etc\nRun this with true to run it.\n");

$db = new DB();
$res = $db->queryExec("UPDATE releases SET relnamestatus = 1 WHERE relnamestatus NOT IN (1, 3, 7)");

if ($res > 0)
	exit("Succesfully reset the relnamestatus of {$res} releases to 1.\n");
else
	exit("No releases to be reseted.\n");

?>
