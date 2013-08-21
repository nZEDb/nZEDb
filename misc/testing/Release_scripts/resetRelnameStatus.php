<?php

/*
 * This script resets the relnamestatus to 1 on every release that has been modified and marked as modified, so you can rerun fixReleaseNames.php
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");

$db = new DB();

$res = $db->queryDirect("update releases set relnamestatus = 1 where relnamestatus != 1");

echo "Succesfully reset the relnamestatus of the releases to 1.\n";


?>
