<?php

require_once dirname(__FILE__) . '/../../../config.php';

$s = new Sites();
$site = $s->get();
$c = new ColorCLI();

// Create the connection here and pass, this is for post processing, so check for alternate
$nntp = new NNTP();
if ($nntp->doConnect() === false) {
	exit($c->error("Unable to connect to usenet."));
}
if ($site->nntpproxy === "1") {
	usleep(500000);
}

$predb = new PreDb(true);
$titles = $predb->updatePre();
$predb->checkPre($nntp);
if ($titles > 0) {
	echo $c->header('Fetched ' . $titles . ' new title(s) from predb sources.');
}
if ($site->nntpproxy != "1") {
	$nntp->doQuit();
}
