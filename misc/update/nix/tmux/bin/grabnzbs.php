<?php

require_once dirname(__FILE__) . '/../../../config.php';

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from grabnzbs_threaded.py."));
}

$s = new Sites();
$site = $s->get();

$alternate = false;
switch ($site->grabnzbs) {
	case '0':
		exit($c->info("Grabnzbs is disabled in site."));
	case '1':
		break;
	case '2':
		$alt = NNTP_SERVER_A;
		if ($alt == '') {
			exit($c->error("You have enabled grabnzbs using the alternate provider, but no provider is set in config.php"));
		}
		$alternate = true;
		break;
	default:
		exit($c->error("Unexpected value for grabnzbs site setting."));
}

$nntp = new NNTP();
if (($alternate ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
	exit($c->error("Unable to connect to usenet."));
}

if ($site->nntpproxy === "1") {
	usleep(500000);
}

$grabnzbs = new GrabNZBs();

if (isset($argv[1])) {
	$grabnzbs->Import($argv[1], $nntp);
} else {
	$grabnzbs->Import($hash = '', $nntp);
}
if ($site->nntpproxy != "1") {
	$nntp->doQuit();
}
