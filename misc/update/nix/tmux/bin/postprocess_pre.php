<?php
require_once dirname(__FILE__) . '/../../../config.php';

$pdo = new \nzedb\db\Settings();
$c = new ColorCLI();

// Create the connection here and pass, this is for post processing, so check for alternate
$nntp = new NNTP();
if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) === false) {
	exit($c->error("Unable to connect to usenet."));
}
if ($pdo->getSetting('nntpproxy') == 1) {
	usleep(500000);
}

$predb = new PreDb(true);
$titles = 0;
$predb->checkPre($nntp);
if ($titles > 0) {
	echo $c->header('Fetched ' . $titles . ' new title(s) from predb sources.');
}
if ($pdo->getSetting('nntpproxy') != 1) {
	$nntp->doQuit();
}
