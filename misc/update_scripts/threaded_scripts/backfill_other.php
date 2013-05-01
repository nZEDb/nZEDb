<?php

require(dirname(__FILE__)."/../config.php");
require_once(WWW_DIR."/lib/backfill.php");
require_once(WWW_DIR."/lib/site.php");

$site = new Sites;
$count = $site->get()->maxmssgs;

$backfill = new Backfill();
$backfill->backfillPostAllGroups($argv[1], $count); 
?>
