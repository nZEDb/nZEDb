<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/tmux.php");

$tmux = new Tmux;
$count = $tmux->get()->BACKFILL_QTY;

$backfill = new Backfill();
$backfill->backfillPostAllGroups($argv[1], $count);

?>
