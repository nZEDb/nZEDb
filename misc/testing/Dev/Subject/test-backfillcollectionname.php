<?php

require_once dirname(__FILE__) . '/../../../../www/config.php';

if (!isset($argv[1])) {
	exit('You must start the script like this (# of articles) : php test-backfillcleansubject.php 20000' . "\n");
} else {
	$pdo = new \nzedb\db\Settings();
	$nntp = new \NNTP(['Settings' => $pdo]);
	if ($nntp->doConnect() !== true) {
		exit($pdo->log->error("Unable to connect to usenet."));
	}
	$backfill = new \Backfill(['NNTP' => $nntp, 'Settings' => $pdo]);
	$groups = new \Groups(['Settings' => $pdo]);
	$grouplist = $groups->getActive();
	foreach ($grouplist as $name) {
		dogroup($name["name"], $argv[1]);
	}
}

function dogroup($name, $articles)
{
	global $backfill, $pdo;

	$backfill->backfillAllGroups($name, $articles);
	echo $pdo->log->primaryOver("Type y and press enter to continue, n to quit.\n");
	if (trim(fgets(fopen("php://stdin", "r"))) == 'y') {
		return true;
	} else {
		exit($pdo->log->primary("Done"));
	}
}
