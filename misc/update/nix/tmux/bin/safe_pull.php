<?php
require_once dirname(__FILE__) . '/../../../config.php';



$c = new ColorCLI();
$pdo = new \nzedb\db\Settings();

if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from safe threaded scripts."));
} else if (isset($argv[1])) {
	// Create the connection here and pass
	$nntp = new NNTP();
	if ($nntp->doConnect() !== true) {
		exit($c->error("Unable to connect to usenet."));
	}

	$nntpProxy = $pdo->getSetting('nntpproxy');

	if ($nntpProxy == "1") {
		usleep(500000);
	}

	$pieces = explode(' ', $argv[1]);
	if (isset($pieces[1]) && $pieces[1] == 'partrepair') {
		$binaries = new Binaries($nntp);
		$groupName = $pieces[0];
		$grp = new Groups();
		$groupArr = $grp->getByName($groupName);
		// Select group, here, only once
		$data = $nntp->selectGroup($groupArr['name']);
		if ($nntp->isError($data)) {
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($data === false) {
				return;
			}
		}
		$binaries->partRepair($groupArr);
	} else if (isset($pieces[1]) && $pieces[0] == 'binupdate') {
		$binaries = new Binaries($nntp);
		$groupName = $pieces[1];
		$grp = new Groups();
		$groupArr = $grp->getByName($groupName);
		$binaries->updateGroup($groupArr);
	} else if (isset($pieces[2]) && ($pieces[2] == 'Binary' || $pieces[2] == 'Backfill')) {
		$backfill = new Backfill($nntp);
		$backfill->getFinal($pieces[0], $pieces[1], $pieces[2]);
	} else if (isset($pieces[2]) && $pieces[2] == 'BackfillAll') {
		$backfill = new Backfill($nntp);
		$backfill->backfillAllGroups($pieces[0], $pieces[1]);
	} else if (isset($pieces[3])) {
		$backfill = new Backfill($nntp);
		$backfill->getRange($pieces[0], $pieces[1], $pieces[2], $pieces[3]);
	} else if (isset($pieces[1])) {
		$backfill = new Backfill($nntp);
		$backfill->backfillAllGroups($pieces[0], $pieces[1]);
	}
	if ($nntpProxy != "1") {
		$nntp->doQuit();
	}
}
