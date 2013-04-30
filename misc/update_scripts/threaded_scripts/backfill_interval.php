<?php

require(dirname(__FILE__)."/../config.php");
require_once(WWW_DIR."/lib/backfill.php");

$backfill = new Backfill();
$backfill->backfillAllGroups($argv[1]);

?>
