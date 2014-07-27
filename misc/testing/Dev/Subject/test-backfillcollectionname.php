<?php

require_once dirname(__FILE__) . '/../../../../www/config.php';

if (!isset($argv[1])) {
	exit('You must start the script like this (# of articles) : php test-backfillcleansubject.php 20000' . "\n");
} else {
	$c = new ColorCLI();
	$pdo = new nzedb\db\Settings();
	$nntp = new NNTP(['Settings' => $pdo, 'ColorCLI' => $c]);
	if ($nntp->doConnect() !== true) {
		exit($c->error("Unable to connect to usenet."));
	}
	$backfill = new Backfill(['NNTP' => $nntp, 'Settings' => $pdo, 'ColorCLI' => $c]);
	$groups = new Groups(['Settings' => $pdo]);
	$grouplist = $groups->getActive();
	foreach ($grouplist as $name) {
		dogroup($name["name"], $argv[1]);
	}
}

function dogroup($name, $articles)
{
	global $backfill, $c;

	$backfill->backfillAllGroups($name, $articles);
	echo $c->primaryOver("Type y and press enter to continue, n to quit.\n");
	if (trim(fgets(fopen("php://stdin", "r"))) == 'y') {
		return true;
	} else {
		exit($c->primary("Done"));
	}
}
