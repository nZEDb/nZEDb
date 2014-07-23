<?php

if (!isset($argv[1])) {
	exit("This script is not intended to be run manually." . PHP_EOL);
}

// Are we coming from python or php ? $options[0] => (string): python|php
// The type of process we want to do: $options[1] => (string): releases
$options = explode('  ', $argv[1]);

switch ($options[1]) {

	// Runs backFill interval or all.
	// $options[2] => (string)group name, Name of group to work on.
	// $options[3] => (int)   backfill type from tmux settings.
	case 'backfill':
		if (in_array((int)$options[3], [1, 2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			$pdo = new \nzedb\db\Settings();
			$nntp = nntp($pdo);
			switch ($options[3]) {
				case 1: // BackFill interval.
					(new Backfill($nntp))->backfillAllGroups($options[2]);
					break;
				case 2: // BackFill all.
					(new Backfill($nntp))->backfillAllGroups($options[2], (new Tmux())->get()->backfill_qty);
					break;
			}
		}
		break;

	// BackFill a single group, 10000 parts.
	// $options[2] => (string)group name, Name of group to work on.
	case 'backfill_all_quick':
		require_once dirname(__FILE__) . '/../../../config.php';
		$pdo = new \nzedb\db\Settings();
		$nntp = nntp($pdo);
		(new Backfill($nntp, true))->backfillAllGroups($options[2], 10000, 'normal');
		break;

	// Process releases.
	// $options[2] => (string)groupCount, number of groups terminated by _ | (int)groupID, group to work on
	case 'releases':
		require_once dirname(__FILE__) . '/../../../config.php';
		$pdo = new nzedb\db\Settings();
		$releases = new ProcessReleases(true, array('Settings' => $pdo, 'ColorCLI' => null, 'ConsoleTools' => new ConsoleTools()));

		//Runs function that are per group
		if (is_numeric($options[2])) {

			if ($options[0] === 'python') {
				collectionCheck($pdo, $options[2]);
			}

			$releases->processIncompleteCollections($options[2]);
			$releases->processCollectionSizes($options[2]);
			$releases->deleteUnwantedCollections($options[2]);
			$releases->createReleases($options[2]);
			$releases->createNZBs($options[2]);
			$releases->deleteCollections($options[2]);

		} else {

			// Run functions that run on releases table after all others completed.
			$groupCount = rtrim($options[2], '_');
			if (!is_numeric($groupCount)) {
				$groupCount = 1;
			}
			$releases->deletedReleasesByGroup();
			$releases->deleteReleases();
			$releases->processRequestIDs('', (5000 * $groupCount), true);
			$releases->processRequestIDs('', (1000 * $groupCount), false);
			$releases->categorizeReleases(1);
		}
		break;

	// Process all local requestID for a single group.
	// $options[2] => (int)groupID, group to work on
	case 'requestid':
		if (is_numeric($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			(new \RequestID(true))->lookupReqIDs($options[2], 5000, true);
		}
		break;


	// Do a single group (update_binaries/backFill/update_releases/postprocess).
	// $options[2] => (int)groupID, group to work on
	case 'update_per_group':
		if (is_numeric($options[2])) {

			require_once dirname(__FILE__) . '/../../../config.php';
			$pdo = new nzedb\db\Settings();

			// Get the group info from MySQL.
			$groupMySQL = $pdo->queryOneRow(sprintf('SELECT * FROM groups WHERE id = %d', $options[2]));

			if ($groupMySQL === false) {
				exit('ERROR: Group not found with ID ' . $options[2] . PHP_EOL);
			}

			// Connect to NNTP.
			$nntp = nntp($pdo);
			$backFill = new Backfill($nntp, true);

			// Update the group for new binaries.
			(new Binaries($nntp, true, $backFill))->updateGroup($groupMySQL);

			// BackFill the group with 20k articles.
			$backFill->backfillAllGroups($groupMySQL['name'], 20000, 'normal');

			// Check if we got anything from binaries/backFill, exit if not.
			collectionCheck($pdo, $options[2]);

			// Create releases.
			$releases = new ProcessReleases(true, array('Settings' => $pdo, 'ColorCLI' => null, 'ConsoleTools' => null));
			$releases->processIncompleteCollections($options[2]);
			$releases->processCollectionSizes($options[2]);
			$releases->deleteUnwantedCollections($options[2]);
			$releases->createReleases($options[2]);
			$releases->createNZBs($options[2]);
			$releases->deleteCollections($options[2]);

			// Post process the releases.
			(new ProcessAdditional(true, $nntp, $pdo))->start($options[2]);
			(new Nfo(true))->processNfoFiles($nntp, $options[2]);

		}
		break;

	// Post process additional and NFO.
	// $options[2] => (char)Letter or number a-f 0-9, first character of release guid.
	case 'pp_additional':
	case 'pp_nfo':
		if (charCheck($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			$pdo = new \nzedb\db\Settings();

			// Create the connection here and pass, this is for post processing, so check for alternate.
			$nntp = new NNTP();
			if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
				exit($c->error('Unable to connect to usenet.'));
			}

			if ($options[1] === 'nfo') {
				(new Nfo(true))->processNfoFiles($nntp, '', $options[2]);
			} else {
				(new ProcessAdditional(true, $nntp, $pdo))->start('', $options[2]);
			}
		}
		break;

	case 'pp_movie':
		if (charCheck($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			(new PostProcess(['Echo' => true, 'Settings' => $pdo]))->processMovies('', $options[2]);
		}
		break;

	case 'pp_tv':
		if (charCheck($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			(new PostProcess(['Echo' => true, 'Settings' => $pdo]))->processTv('', $options[2]);
		}
		break;
}

/**
 * Check if the character contains a-f or 0-9.
 *
 * @param string $char
 *
 * @return bool
 */
function charCheck($char)
{
	if (in_array($char, ['a','b','c','d','e','f','0','1','2','3','4','5','6','7','8','9'])) {
		return true;
	}
	return false;
}

/**
 * Check if the group should be processed.
 *
 * @param \nzedb\db\Settings $pdo
 * @param int                $groupID
 */
function collectionCheck(&$pdo, $groupID)
{
	if ($pdo->queryOneRow(sprintf('SELECT id FROM collections_%d LIMIT 1', $groupID)) === false) {
		exit();
	}
}

/**
 * Connect to usenet, return NNTP object.
 *
 * @param \nzedb\db\Settings $pdo
 *
 * @return NNTP
 */
function &nntp(&$pdo)
{
	$nntp = new NNTP();
	if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
		exit("ERROR: Unable to connect to usenet." . PHP_EOL);
	}

	return $nntp;
}