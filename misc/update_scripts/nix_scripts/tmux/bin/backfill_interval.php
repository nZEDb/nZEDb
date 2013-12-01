<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'backfill.php';
require_once nZEDb_LIB . 'tmux.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'site.php';

$c = new ColorCLI;
$s = new Sites();
$site = $s->get();
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from backfill_threaded.py."));
else if (isset($argv[1]))
{
	// Create the connection here and pass
	$nntp = new Nntp();
	if ($nntp->doConnect() === false)
		exit($c->error("Unable to connect to usenet."));
	if ($site->nntpproxy === "1")
		usleep(500000);

	$pieces = explode(' ', $argv[1]);
	if (isset($pieces[1]) && $pieces[1] == 1)
	{
		$backfill = new Backfill();
		$backfill->backfillAllGroups($pieces[0], $nntp);
	}
	else if (isset($pieces[1]) && $pieces[1] == 2)
	{
		$tmux = new Tmux();
		$count = $tmux->get()->backfill_qty;
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups($pieces[0], $count, $type='', $nntp);
	}
	if ($site->nntpproxy != "1")
		$nntp->doQuit();
}
?>
