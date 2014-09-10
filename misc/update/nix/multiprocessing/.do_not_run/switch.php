<?php

use \nzedb\processing\PostProcess;

if (!isset($argv[1])) {
	exit("This script is not intended to be run manually." . PHP_EOL);
}

require_once dirname(__FILE__) . '/../../../config.php';

// Are we coming from python or php ? $options[0] => (string): python|php
// The type of process we want to do: $options[1] => (string): releases
$options = explode('  ', $argv[1]);

switch ($options[1]) {

	// Runs backFill interval or all.
	// $options[2] => (string)group name, Name of group to work on.
	// $options[3] => (int)   backfill type from tmux settings. 1 = Backfill interval , 2 = Bakfill all
	case 'backfill':
		if (in_array((int)$options[3], [1, 2])) {
			$pdo = new \nzedb\db\Settings();
			$value = $pdo->queryOneRow("SELECT value FROM tmux WHERE setting = 'backfill_qty'");
			if ($value !== false) {
				$nntp = nntp($pdo);
				(new \Backfill(['NNTP' => $nntp, 'Settings' => $pdo]))->backfillAllGroups($options[2], ($options[3] == 1 ? '' : $value['value']));
			}
		}
		break;

	/*  BackFill up to x number of articles for all groups.
	 *
	 * $options[2] => (string) Group name.
	 * $options[3] => (int)    Quantity of articles to download.
	 */
	case 'backfill_all_quantity':
		$pdo = new \nzedb\db\Settings();
		$nntp = nntp($pdo);
		(new \Backfill(['NNTP' => $nntp, 'Settings' => $pdo]))->backfillAllGroups($options[2], $options[3]);
		break;

	// BackFill a single group, 10000 parts.
	// $options[2] => (string)group name, Name of group to work on.
	case 'backfill_all_quick':
		$pdo = new \nzedb\db\Settings();
		$nntp = nntp($pdo);
		(new \Backfill(['NNTP' => $nntp, 'Settings' => $pdo], true))->backfillAllGroups($options[2], 10000, 'normal');
		break;

	/* Get a range of article headers for a group.
	 *
	 * $options[2] => (string) backfill/binaries
	 * $options[3] => (string) Group name.
	 * $options[4] => (int)    First article number in range.
	 * $options[5] => (int)    Last article number in range.
	 * $options[6] => (int)    Number of threads.
	 */
	case 'get_range':
		$pdo = new \nzedb\db\Settings();
		$nntp = nntp($pdo);
		$groups = new \Groups(['Settings' => $pdo]);
		$groupMySQL = $groups->getByName($options[3]);
		if ($nntp->isError($nntp->selectGroup($groupMySQL['name']))) {
			if ($nntp->isError($nntp->dataError($nntp, $groupMySQL['name']))) {
				return;
			}
		}
		$binaries = new \Binaries(['NNTP' => $nntp, 'Settings' => $pdo, 'Groups' => $groups]);
		$return = $binaries->scan($groupMySQL, $options[4], $options[5], ($pdo->getSetting('safepartrepair') == 1 ? 'update' : 'backfill'));
		if (empty($return)) {
			exit();
		}
		$columns = [];
		switch ($options[2]) {
			case 'binaries':
				if ($return['lastArticleNumber'] <= $groupMySQL['last_record']){
					exit();
				}
				$columns[1] = sprintf(
					'last_record_postdate = %s',
					$pdo->from_unixtime(
						(is_numeric($return['lastArticleDate']) ? $return['lastArticleDate'] : strtotime($return['lastArticleDate']))
					)
				);
				$columns[2] = sprintf('last_record = %s', $return['lastArticleNumber']);
				$query = sprintf(
					'UPDATE groups SET %s, %s, last_updated = NOW() WHERE id = %d AND last_record < %s',
					$columns[1],
					$columns[2],
					$groupMySQL['id'],
					$return['lastArticleNumber']
				);
				break;
			case 'backfill':
				if ($return['firstArticleNumber'] >= $groupMySQL['first_record']){
					exit();
				}
				$columns[1] = sprintf(
					'first_record_postdate = %s',
					$pdo->from_unixtime(
						(is_numeric($return['firstArticleDate']) ? $return['firstArticleDate'] : strtotime($return['firstArticleDate']))
					)
				);
				$columns[2] = sprintf('first_record = %s', $return['firstArticleNumber']);
				$query = sprintf(
					'UPDATE groups SET %s, %s, last_updated = NOW() WHERE id = %d AND first_record > %s',
					$columns[1],
					$columns[2],
					$groupMySQL['id'],
					$return['firstArticleNumber']
				);
				break;
			default:
				exit();
		}
		$pdo->queryExec($query);
		break;

	/* Do part repair for a group.
	 *
	 * $options[2] => (string) Group name.
	 */
	case 'part_repair':
		$pdo = new \nzedb\db\Settings();
		$groups = new \Groups(['Settings' => $pdo]);
		$groupMySQL = $groups->getByName($options[2]);
		$nntp = nntp($pdo);
		// Select group, here, only once
		$data = $nntp->selectGroup($groupMySQL['name']);
		if ($nntp->isError($data)) {
			if ($nntp->dataError($nntp, $groupMySQL['name']) === false) {
				exit();
			}
		}
		(new \Binaries(['NNTP' => $nntp, 'Groups' => $groups, 'Settings' => $pdo]))->partRepair($groupMySQL);
		break;

	// Process releases.
	// $options[2] => (string)groupCount, number of groups terminated by _ | (int)groupID, group to work on
	case 'releases':
		$pdo = new \nzedb\db\Settings();
		$releases = new \nzedb\processing\ProcessReleases(['Settings' => $pdo]);

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
			(new \RequestIDLocal(['Echo' => true]))->lookupRequestIDs(['GroupID' => $options[2], 'limit' => 5000]);
		}
		break;

	/* Update a single group's article headers.
	 *
	 * $options[2] => (string) Group name.
	 */
	case 'update_group_headers':
		$pdo = new \nzedb\db\Settings();
		$nntp = nntp($pdo);
		$groups = new \Groups(['Settings' => $pdo]);
		$groupMySQL = $groups->getByName($options[2]);
		(new \Binaries(['NNTP' => $nntp, 'Groups' => $groups, 'Settings' => $pdo]))->updateGroup($groupMySQL);
		break;


	// Do a single group (update_binaries/backFill/update_releases/postprocess).
	// $options[2] => (int)groupID, group to work on
	case 'update_per_group':
		if (is_numeric($options[2])) {

			$pdo = new \nzedb\db\Settings();

			// Get the group info from MySQL.
			$groupMySQL = $pdo->queryOneRow(sprintf('SELECT * FROM groups WHERE id = %d', $options[2]));

			if ($groupMySQL === false) {
				exit('ERROR: Group not found with ID ' . $options[2] . PHP_EOL);
			}

			// Connect to NNTP.
			$nntp = nntp($pdo);
			$backFill = new \Backfill(['NNTP' => $nntp, 'Settings' => $pdo], true);

			// Update the group for new binaries.
			(new \Binaries(['NNTP' => $nntp, 'Settings' => $pdo]))->updateGroup($groupMySQL);

			// BackFill the group with 20k articles.
			$backFill->backfillAllGroups($groupMySQL['name'], 20000, 'normal');

			// Check if we got anything from binaries/backFill, exit if not.
			collectionCheck($pdo, $options[2]);

			// Create releases.
			processReleases(new \nzedb\processing\ProcessReleases(['Settings' => $pdo]), $options[2]);

			// Post process the releases.
			(new \nzedb\processing\post\ProcessAdditional(['Echo' => true, 'NNTP' => $nntp, 'Settings' => $pdo]))->start($options[2]);
			(new \Nfo(['Echo' => true, 'Settings' => $pdo]))->processNfoFiles($nntp, $options[2]);

		}
		break;

	// Post process additional and NFO.
	// $options[2] => (char)Letter or number a-f 0-9, first character of release guid.
	case 'pp_additional':
	case 'pp_nfo':
		if (charCheck($options[2])) {
			$pdo = new \nzedb\db\Settings();

			// Create the connection here and pass, this is for post processing, so check for alternate.
			$nntp = nntp($pdo, true);

			if ($options[1] === 'pp_nfo') {
				(new \Nfo(['Echo' => true, 'Settings' => $pdo]))->processNfoFiles($nntp, '', $options[2]);
			} else {
				(new \nzedb\processing\post\ProcessAdditional(['Echo' => true, 'NNTP' => $nntp, 'Settings' => $pdo]))->start('', $options[2]);
			}
		}
		break;

	/* Post process movies.
	 *
	 * $options[2] (char) Single character, first letter of release guid.
	 * $options[3] (int)  Process all releases or renamed releases only.
	 */
	case 'pp_movie':
		if (charCheck($options[2])) {
			$pdo = new \nzedb\db\Settings();
			(new \nzedb\processing\PostProcess(['Settings' => $pdo]))->processMovies('', $options[2], (isset($options[3]) ? $options[3] : ''));
		}
		break;

	/* Post process TV.
	 *
	 * $options[2] (char) Single character, first letter of release guid.
	 * $options[3] (int)  Process all releases or renamed releases only.
	 */
	case 'pp_tv':
		if (charCheck($options[2])) {
			$pdo = new \nzedb\db\Settings();
			(new \nzedb\processing\PostProcess(['Settings' => $pdo]))->processTv('', $options[2], (isset($options[3]) ? $options[3] : ''));
		}
		break;
}

/**
 * Create / process releases for a groupID.
 *
 * @param \nzedb\processing\ProcessReleases $releases
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
 * @param bool               $alternate Use alternate NNTP provider.
 *
 * @return NNTP
 */
function &nntp(&$pdo, $alternate = false)
{
	$nntp = new \NNTP(['Settings' => $pdo]);
	if (($alternate && $pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
		exit("ERROR: Unable to connect to usenet." . PHP_EOL);
	}

	return $nntp;
}
