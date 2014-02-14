<?php

require_once dirname(__FILE__) . '/../../../config.php';

$c = new ColorCLI();
$s = new Sites();
$site = $s->get();

if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from safe threaded scripts."));
} else if (isset($argv[1])) {
	// Create the connection here and pass
	$nntp = new NNTP();
	if ($nntp->doConnect() === false) {
		exit($c->error("Unable to connect to usenet."));
	}
	if ($site->nntpproxy === "1") {
		usleep(500000);
	}

	$pieces = explode(' ', $argv[1]);
	if (isset($pieces[1]) && $pieces[1] == 'partrepair') {
		$binaries = new Binaries();
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
		$binaries->partRepair($nntp, $groupArr);
	} else if (isset($pieces[1]) && $pieces[0] == 'binupdate') {
		$binaries = new Binaries();
		$groupName = $pieces[1];
		$grp = new Groups();
		$groupArr = $grp->getByName($groupName);
		$binaries->updateGroup($groupArr, $nntp);
	} else if (isset($pieces[2]) && ($pieces[2] == 'Binary' || $pieces[2] == 'Backfill')) {
		$backfill = new Backfill();
		$backfill->getFinal($pieces[0], $pieces[1], $pieces[2], $nntp);
	} else if (isset($pieces[2]) && $pieces[2] == 'BackfillAll') {
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups($nntp, $pieces[0], $pieces[1], $type = '');
	} else if (isset($pieces[3])) {
		$backfill = new Backfill();
		$backfill->getRange($pieces[0], $pieces[1], $pieces[2], $pieces[3], $nntp);
	} else if (isset($pieces[1])) {
		$backfill = new Backfill();
		$backfill->backfillPostAllGroups($nntp, $pieces[0], $pieces[1], $type = '');
	}
	if ($site->nntpproxy != "1") {
		$nntp->doQuit();
	}
}
