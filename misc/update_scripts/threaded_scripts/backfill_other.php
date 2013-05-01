<?php

require(dirname(__FILE__)."/../config.php");
require_once(WWW_DIR."/lib/backfill.php");

$backfill = new Backfill();
$backfill->backfillPostAllGroups($argv[1], 20000); 
?>
