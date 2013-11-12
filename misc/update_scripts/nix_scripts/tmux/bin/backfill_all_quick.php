<?php
require_once(dirname(__FILE__).'/../../../config.php');
require_once(WWW_DIR.'lib/backfill.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/ColorCLI.php');

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from update_threaded.py.\n"));
$nntp = new Nntp();
if ($nntp->doConnect() === false)
{
	echo $c->error("Unable to connect to usenet.\n");
	return;
}

$pieces = explode(' ', $argv[1]);
$backfill = new Backfill();
$backfill->backfillPostAllGroups($pieces[0], 10000, 'normal', $nntp);
$nntp->doQuit();
