<?php
require_once dirname(__FILE__) . '/../../../config.php';

$c = new ColorCLI();

if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from releases_threaded.py."));
}

$pieces = explode('  ', $argv[1]);
$groupID = $pieces[1];

$pdo = new nzedb\db\Settings();
$releases = new ProcessReleases(true, array('Settings' => $pdo, 'ColorCLI' => $c, 'ConsoleTools' => new ConsoleTools()));

switch (true) {
	case is_numeric($groupID):
		if ($pieces[0] === 'tmux') {
			// Don't even process the group if no collections
			$test = $pdo->queryOneRow(
				sprintf('
					SELECT id
					FROM collections_%d
					LIMIT 1',
					$groupID
				)
			);
			if ($test === false) {
				exit();
			}
		}
		//Runs function that are per group
		$releases->processIncompleteCollections($groupID);
		$releases->processCollectionSizes($groupID);
		$releases->deleteUnwantedCollections($groupID);
		$releases->createReleases($groupID);
		$releases->createNZBs($groupID);
		$releases->processRequestIDs($groupID, 1000, false);
		$releases->deleteCollections($groupID);
		break;
	case $groupID === 'Stage7b':
		// Runs functions that run on releases table after all others completed
		$releases->deletedReleasesByGroup();
		$releases->processRequestIDs('', 5000, true);
		$releases->categorizeReleases(1);
		$releases->deleteReleases();
		break;
	default:
		exit;
}
