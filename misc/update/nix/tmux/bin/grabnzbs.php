<?php

require_once dirname(__FILE__) . '/../../../config.php';

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from grabnzbs_threaded.py."));
}

$s = new Sites();
$site = $s->get();

$nntp = new NNTP();
if (($site->grabnzbs == '2' ? $nntp->doConnect(true, true) : $nntp->doConnect()) === false) {
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
