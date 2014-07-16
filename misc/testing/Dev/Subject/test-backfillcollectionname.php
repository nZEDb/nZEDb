<?php

require_once dirname(__FILE__) . '/../../../../www/config.php';

if (!isset($argv[1])) {
	exit('You must start the script like this (# of articles) : php test-backfillcleansubject.php 20000' . "\n");
} else {
	$groups = new Groups();
	$grouplist = $groups->getActive();
	foreach ($grouplist as $name) {
		dogroup($name["name"], $argv[1]);
	}
}

function dogroup($name, $articles)
{
	$c = new ColorCLI();
	$nntpProxy = (new nzedb\db\Settings())->getSetting('nntpproxy');
	$nntp = new NNTP();
	if ($nntp->doConnect() !== true) {
		exit($c->error("Unable to connect to usenet."));
	}
	if ($nntpProxy == "1") {
		usleep(500000);
	}
	$backfill = new Backfill($nntp);
	$backfill->backfillAllGroups($name, $articles);
	echo $c->primaryOver("Type y and press enter to continue, n to quit.\n");
	if (trim(fgets(fopen("php://stdin", "r"))) == 'y') {
		return true;
	} else {
		exit($c->primary("Done"));
	}
}
