<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");

$pieces = explode(" ", $argv[1]);
$backfill = new Backfill();
$backfill->backfillPostAllGroups($pieces[0], 5000);
