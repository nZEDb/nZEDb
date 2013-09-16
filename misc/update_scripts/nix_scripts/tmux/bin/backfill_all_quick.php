<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");

$pieces = explode(" ", $argv[1]);
$backfill = new Backfill();
$backfill->backfillPostAllGroups(trim($pieces[0],"'"), 10000, "normal");
