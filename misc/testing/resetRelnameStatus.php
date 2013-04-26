<?php

/*
 * This script resets the relnamestatus to 1 on every release that has relnamestatus 2, so you can rerun fixReleaseNames.php
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");

$db = new Db;

$reset = $db->query("update releases set relnamestatus = 1 where relnamestatus = 2");
echo count($reset)." releases have had their relnamestatus set to 1.\n";

?>
