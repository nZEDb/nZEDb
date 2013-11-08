<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/tmux.php");

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	if (isset($pieces[1]) && $pieces[1] == 1)
	{
		$backfill = new Backfill();
		$backfill->backfillAllGroups($pieces[0]);
	}
	elseif (isset($pieces[1]) && $pieces[1] == 2)
	{
		$tmux = new Tmux();
		$count = $tmux->get()->backfill_qty;
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups($pieces[0], $count);
	}
}
