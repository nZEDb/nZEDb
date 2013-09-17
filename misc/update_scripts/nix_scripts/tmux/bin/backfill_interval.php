<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/tmux.php");

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	if (isset($pieces[1]) && trim($pieces[1],"'") == 1)
	{
		$backfill = new Backfill();
		$backfill->backfillAllGroups(trim($pieces[0],"'"));
	}
	elseif (isset($pieces[1]) && trim($pieces[1],"'") == 2)
	{
		$tmux = new Tmux();
		$count = $tmux->get()->BACKFILL_QTY;
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups(trim($pieces[0],"'"), $count);
	}
}
