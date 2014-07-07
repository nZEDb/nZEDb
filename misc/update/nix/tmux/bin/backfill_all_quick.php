<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$c = new ColorCLI();

if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from backfill_threaded.py."));
}

// Create the connection here and pass
$nntp = new NNTP();
if ($nntp->doConnect() !== true) {
	exit($c->error("Unable to connect to usenet."));
}

$nntpProxy = (new Settings())->getSetting('nntpproxy');

if ($nntpProxy == "1") {
	usleep(500000);
}

$pieces = explode(' ', $argv[1]);
$backfill = new Backfill($nntp);
$backfill->backfillAllGroups($pieces[0], 10000, 'normal');

if ($nntpProxy != "1") {
	$nntp->doQuit();
}
