<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");

$backfill = new Backfill();
$backfill->backfillPostAllGroups($argv[1], 5000);

?>

