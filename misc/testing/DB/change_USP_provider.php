<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

/* This script will update the groups table to get the new article numbers for each group you have activated.
  It will also truncate the parts, binaries, collections, and partsrepair tables.
 */
// TODO: Make this threaded so it goes faster.

$pdo = new Settings();

if (!isset($argv[1]) || $argv[1] != 'true') {
	printf($pdo->log->setColor('Yellow') . "This script is used when you have switched UseNet Providers(USP) so you can pickup where you left off, rather than resetting all the groups.\nOnly use this script after you have updated your config.php file with your new USP info!!\nMake sure you " . $pdo->log->setColor('Red', 'Bold') . "DO NOT" . $pdo->log->setcolor('Yellow') . " have any update or postprocess scripts running when running this script!\n\n" . $pdo->log->setColor('Cyan') . "Usage: php change_USP_provider true\n");
	exit();
}


$groups = $pdo->query("SELECT id, name, first_record_postdate, last_record_postdate FROM groups WHERE active = 1");
$numofgroups = count($groups);
$guesstime = $numofgroups * 2;
$totalstart = microtime(true);

echo "You have $numofgroups active, it takes about 2 minutes on average to processes each group.\n";
foreach ($groups as $group) {
	$starttime = microtime(true);
	$nntp = new \NNTP(['Settings' => $pdo]);
	if ($nntp->doConnect() !== true) {
		return;
	}
	//printf("Updating group ".$group['name']."..\n");
	$bfdays = daysOldstr($group['first_record_postdate']);
	$currdays = daysOldstr($group['last_record_postdate']);
	$bfartnum = daytopost($nntp, $group['name'], $bfdays, true, true);
	echo "Our Current backfill postdate was: " . $pdo->log->setColor('Yellow') . date('r', strtotime($group['first_record_postdate'])) . $pdo->log->rsetcolor() . "\n";
	$currartnum = daytopost($nntp, $group['name'], $currdays, true, false);
	echo "Our Current current postdate was: " . $pdo->log->setColor('Yellow') . date('r', strtotime($group['last_record_postdate'])) . $pdo->log->rsetcolor() . "\n";
	$pdo->queryExec(sprintf("UPDATE groups SET first_record = %s, last_record = %s WHERE id = %d", $pdo->escapeString($bfartnum), $pdo->escapeString($currartnum), $group['id']));
	$endtime = microtime(true);
	echo $pdo->log->setColor('Gray', 'Dim') . "This group took " . gmdate("H:i:s", $endtime - $starttime) . " to process.\n";
	$numofgroups--;
	echo "There are " . $numofgroups . " left to process.\n\n" . $pdo->log->rsetcolor() . "";
}

$totalend = microtime(true);
echo $pdo->log->header('Total time to update all groups ' . gmdate("H:i:s", $totalend - $totalstart));

// Truncate tables to complete the change to the new USP.
$arr = array("parts", "partrepair", "binaries", "collections");
foreach ($arr as &$value) {
	$rel = $pdo->queryExec("TRUNCATE TABLE $value");
	if ($rel !== false) {
		echo $pdo->log->header("Truncating $value completed.");
	}
}
unset($value);

function daysOldstr($timestamp)
{
	return round((time() - strtotime($timestamp)) / 86400, 5);
}

function daysOld($timestamp)
{
	return round((time() - $timestamp) / 86400, 5);
}

// This function taken from lib/backfill.php, and modified to fit our needs.
function daytopost($nntp, $group, $days, $debug = true, $bfcheck = true)
{
	global $pdo;

	$st = false;
	if ($debug && $bfcheck) {
		echo $pdo->log->primary('Finding start and end articles for ' . $group . '.');
	}

	if (!isset($nntp)) {
		$nntp = new \NNTP(['Settings' => $pdo]);
		if ($nntp->doConnect(false) !== true) {
			return;
		}

		$st = true;
	}

	$binaries = new \Binaries(['NNTP' => $nntp, 'Settings' => $pdo]);

	$data = $nntp->selectGroup($group);
	if ($nntp->isError($data)) {
		$data = $nntp->dataError($nntp, $group, false);
		if ($data === false) {
			return;
		}
	}

	// Goal timestamp.
	$goaldate = date('U') - (86400 * $days);
	$totalnumberofarticles = $data['last'] - $data['first'];
	$upperbound = $data['last'];
	$lowerbound = $data['first'];

	if ($debug && $bfcheck) {
		echo $pdo->log->header('Total Articles: ' . number_format($totalnumberofarticles) . ' Newest: ' . number_format($upperbound) . ' Oldest: ' . number_format($lowerbound));
	}

	if ($data['last'] == PHP_INT_MAX) {
		exit($pdo->log->error("Group data is coming back as php's max value. You should not see this since we use a patched Net_NNTP that fixes this bug."));
	}

	$firstDate = $binaries->postdate($data['first'], $data);
	$lastDate = $binaries->postdate($data['last'], $data);

	if ($goaldate < $firstDate && $bfcheck) {
		if ($st === true) {
			$nntp->doQuit();
		}
		echo $pdo->log->warning("The oldest post indexed from $days day(s) ago is older than the first article stored on your news server.\nSetting to First available article of (date('r', $firstDate) or daysOld($firstDate) days).");
		return $data['first'];
	} else if ($goaldate > $lastDate && $bfcheck) {
		if ($st === true) {
			$nntp->doQuit();
		}
		echo $pdo->log->error("ERROR: The oldest post indexed from $days day(s) ago is newer than the last article stored on your news server.\nTo backfill this group you need to set Backfill Days to at least ceil(daysOld($lastDate)+1) days (date('r', $lastDate-86400).");
		return '';
	}

	if ($debug && $bfcheck) {
		echo $pdo->log->primary("Searching for postdates.\nGroup's Firstdate: " . $firstDate . ' (' . ((is_int($firstDate)) ? date('r', $firstDate) : 'n/a') . ").\nGroup's Lastdate: " . $lastDate . ' (' . date('r', $lastDate) . ").");
	}

	$interval = floor(($upperbound - $lowerbound) * 0.5);
	$templowered = '';
	$dateofnextone = $lastDate;
	// Match on days not timestamp to speed things up.
	while (daysOld($dateofnextone) < $days) {
		while (($tmpDate = $binaries->postdate(($upperbound - $interval), $data)) > $goaldate) {
			$upperbound = $upperbound - $interval;
		}

		if (!$templowered) {
			$interval = ceil(($interval / 2));
		}
		$dateofnextone = $binaries->postdate(($upperbound - 1), $data);
		while (!$dateofnextone) {
			$dateofnextone = $binaries->postdate(($upperbound - 1), $data);
		}
	}
	if ($st === true) {
		$nntp->doQuit();
	}
	if ($bfcheck) {
		echo $pdo->log->header("\nBackfill article determined to be " . $upperbound . " " . $pdo->log->setColor('Yellow') . "(" . date('r', $dateofnextone) . ")" . $pdo->log->rsetcolor());
	} // which is '.daysOld($dateofnextone)." days old.\n";
	else {
		echo $pdo->log->header('Current article determined to be ' . $upperbound . " " . $pdo->log->setColor('Yellow') . "(" . date('r', $dateofnextone) . ")" . $pdo->log->rsetcolor());
	} // which is '.daysOld($dateofnextone)." days old.\n";
	return $upperbound;
}
