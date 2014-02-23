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
	$s = new Sites();
	$site = $s->get();
	$nntp = new NNTP();
	if (($site->alternate_nntp == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) === false) {
		exit($c->error("Unable to connect to usenet."));
	}
	if ($site->nntpproxy === "1") {
		usleep(500000);
	}
	$backfill = new Backfill();
	$backfill->backfillPostAllGroups($nntp, $name, $articles);
	echo $c->primaryOver("Type y and press enter to continue, n to quit.\n");
	if (trim(fgets(fopen("php://stdin", "r"))) == 'y') {
		return true;
	} else {
		exit($c->primary("Done"));
	}
}
