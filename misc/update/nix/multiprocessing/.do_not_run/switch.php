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
	// $options[3] => (int)   backfill type from tmux settings. 1 = Backfill interval , 2 = Bakfill all
	case 'backfill':
		if (in_array((int)$options[3], [1, 2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			$pdo = new \nzedb\db\Settings();
			$value = $pdo->queryOneRow("SELECT value FROM tmux WHERE setting = 'backfill_qty'");
			if ($value !== false) {
				$nntp = nntp($pdo);
				(new Backfill(['NNTP' => $nntp, 'Settings' => $pdo]))->backfillAllGroups($options[2], ($options[3] == 1 ? '' : $value['value']));
			}
		}
		break;

	// BackFill a single group, 10000 parts.
	// $options[2] => (string)group name, Name of group to work on.
	case 'backfill_all_quick':
		require_once dirname(__FILE__) . '/../../../config.php';
		$pdo = new \nzedb\db\Settings();
		$nntp = nntp($pdo);
		(new Backfill(['NNTP' => $nntp, 'Settings' => $pdo], true))->backfillAllGroups($options[2], 10000, 'normal');
		break;

	// Process releases.
	// $options[2] => (string)groupCount, number of groups terminated by _ | (int)groupID, group to work on
	case 'releases':
		require_once dirname(__FILE__) . '/../../../config.php';
		$pdo = new nzedb\db\Settings();
		$releases = new ProcessReleases(['Settings' => $pdo]);

		//Runs function that are per group
		if (is_numeric($options[2])) {

			if ($options[0] === 'python') {
				collectionCheck($pdo, $options[2]);
			}

			processReleases($releases, $options[2]);

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
			$releases->categorizeReleases(2);
		}
		break;

	// Process all local requestID for a single group.
	// $options[2] => (int)groupID, group to work on
	case 'requestid':
		if (is_numeric($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			(new \RequestIDLocal(['Echo' => true]))->lookupRequestIDs(['GroupID' => $options[2], 'limit' => 5000]);
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
			$backFill = new Backfill(['NNTP' => $nntp, 'Settings' => $pdo], true);

			// Update the group for new binaries.
			(new Binaries(['NNTP' => $nntp, 'Backfill' => $backFill, 'Settings' => $pdo]))->updateGroup($groupMySQL);

			// BackFill the group with 20k articles.
			$backFill->backfillAllGroups($groupMySQL['name'], 20000, 'normal');

			// Check if we got anything from binaries/backFill, exit if not.
			collectionCheck($pdo, $options[2]);

			// Create releases.
			processReleases(new ProcessReleases(['Settings' => $pdo]), $options[2]);

			// Post process the releases.
			(new ProcessAdditional(['Echo' => true, 'NNTP' => $nntp, 'Settings' => $pdo]))->start($options[2]);
			(new Nfo(['Echo' => true, 'Settings' => $pdo]))->processNfoFiles($nntp, $options[2]);

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
			$nntp = new NNTP(['Settings' => $pdo]);
			if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
				exit('Unable to connect to usenet.');
			}

			if ($options[1] === 'pp_nfo') {
				(new Nfo(['Echo' => true, 'Settings' => $pdo]))->processNfoFiles($nntp, '', $options[2]);
			} else {
				(new ProcessAdditional(['Echo' => true, 'NNTP' => $nntp, 'Settings' => $pdo]))->start('', $options[2]);
			}
		}
		break;

	case 'pp_movie':
		if (charCheck($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			$pdo = new \nzedb\db\Settings();
			(new PostProcess(['Settings' => $pdo]))->processMovies('', $options[2]);
		}
		break;

	case 'pp_tv':
		if (charCheck($options[2])) {
			require_once dirname(__FILE__) . '/../../../config.php';
			$pdo = new \nzedb\db\Settings();
			(new PostProcess(['Settings' => $pdo]))->processTv('', $options[2]);
		}
		break;
}

/**
 * Create / process releases for a groupID.
 *
 * @param ProcessReleases $releases
 * @param int             $groupID
 */
function processReleases($releases, $groupID)
{
	$releases->processIncompleteCollections($groupID);
	$releases->processCollectionSizes($groupID);
	$releases->deleteUnwantedCollections($groupID);
	$releases->createReleases($groupID);
	$releases->createNZBs($groupID);
	$releases->deleteCollections($groupID);
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