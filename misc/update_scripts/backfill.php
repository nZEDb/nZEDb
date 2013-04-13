<?php

require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/backfill.php");

if (isset($argv[1]))
	$groupName = $argv[1];
else
	$groupName = '';

$backfill = new Backfill();
$backfill->backfillAllGroups($groupName);

?>
