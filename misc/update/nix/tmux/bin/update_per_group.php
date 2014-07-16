<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from update_threaded.py."));
}

$pdo = new Settings();
$pieces = explode('  ', $argv[1]);
$groupid = $pieces[0];
$consoletools = new ConsoleTools();
$releases = new ProcessReleases(true, array('Settings' => $pdo, 'ColorCLI' => $c, 'ConsoleTools' => $consoletools));
$groups = new Groups();
$groupname = $groups->getByNameByID($groupid);
$group = $groups->getByName($groupname);

// Create the connection here and pass, this is for post processing, so check for alternate
$nntp = new NNTP();
if (($pdo->getSetting('alternate_nntp') == '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
	exit($c->error("Unable to connect to usenet."));
}
$binaries = new Binaries($nntp);
$backfill = new Backfill($nntp);
$nntpProxy = (new Settings())->getSetting('nntpproxy');
if ($nntpProxy == "1") {
	usleep(500000);
}

if ($pieces[0] != 'Stage7b') {
	// Update Binaries per group
	$binaries->updateGroup($group);

	// Backfill per group
	$backfill->backfillAllGroups($groupname, 20000, 'normal');

	// Update Releases per group
	try {
		$test = $pdo->prepare('SELECT * FROM collections_' . $pieces[0]);
		$test->execute();
		// Don't even process the group if no collections
		if ($test->rowCount() == 0) {
			//$mask = "%-30.30s has %s collections, skipping.\n";
			//printf($mask, str_replace('alt.binaries', 'a.b', $groupname), number_format($test->rowCount()));
			exit();
		}
	} catch (PDOException $e) {
		//No collections available
		//exit($groupname." has no collections to process\n");
		exit();
	}

	// Runs function that are per group
	$releases->processIncompleteCollections($groupid);
	$releases->processCollectionSizes($groupid);
	$releases->deleteUnwantedCollections($groupid);
	$retcount = $releases->createReleases($groupid);
	$releases->createNZBs($groupid);
	$releases->deleteCollections($groupid);
//	$mask = "%-30.30s added %s releases.\n";
//	$first = number_format($retcount);
//	if($retcount > 0)
//		printf($mask, str_replace('alt.binaries', 'a.b', $groupname), $first);

	$postprocess = new PostProcess(true);
	$postprocess->processAdditional($nntp, '', $groupid);
	$nfopostprocess = new Nfo(true);
	$nfopostprocess->processNfoFiles(null, null, null, $groupid, $nntp);
	if ($nntpProxy != "1") {
		$nntp->doQuit();
	}
} else if ($pieces[0] == 'Stage7b') {
	// Runs functions that run on releases table after all others completed
	$groupid = '';
	$releases->deletedReleasesByGroup($groupid);
	$releases->categorizeReleases(1, $groupid);
	$releases->deleteReleases();
	//echo 'Deleted '.number_format($deleted)." collections/binaries/parts.\n";
}
