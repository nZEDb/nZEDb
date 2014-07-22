<?php
require_once dirname(__FILE__) . '/../../../config.php';

$c = new ColorCLI();

if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from releases_threaded.py."));
}

$pieces = explode('  ', $argv[1]);
// [0] => (string)tmux|(string)php
// [1] => (int)groupCount|(string)ignore
// [2] => (int)groupID|(string)ignore


$pdo = new nzedb\db\Settings();
$releases = new ProcessReleases(true, array('Settings' => $pdo, 'ColorCLI' => $c, 'ConsoleTools' => new ConsoleTools()));

switch (true) {
	case is_numeric($pieces[2]):
		if ($pieces[0] === 'tmux') {
			// Don't even process the group if no collections
			$test = $pdo->queryOneRow(
				sprintf('
					SELECT id
					FROM collections_%d
					LIMIT 1',
					$pieces[2]
				)
			);
			if ($test === false) {
				exit();
			}
		}
		//Runs function that are per group
		$releases->processIncompleteCollections($pieces[2]);
		$releases->processCollectionSizes($pieces[2]);
		$releases->deleteUnwantedCollections($pieces[2]);
		$releases->createReleases($pieces[2]);
		$releases->createNZBs($pieces[2]);
		$releases->deleteCollections($pieces[2]);
		break;
	case $pieces[2] === 'ignore':
		// Runs functions that run on releases table after all others completed
		$releases->deletedReleasesByGroup();
		$releases->deleteReleases();
		$releases->processRequestIDs('', (5000 * $pieces[1]), true);
		$releases->processRequestIDs('', (1000 * $pieces[1]), false);
		$releases->categorizeReleases(1);
		break;
	default:
		exit;
}
