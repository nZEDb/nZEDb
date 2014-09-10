<?php

if (!isset($argv[1])) {
	exit(
		'This script will set where backfill starts based on your oldest release in a group or groups,' .
		' so you do not have to re-download and process all the headers you already downloaded.' . PHP_EOL .
		'This is good if you imported NZBs or reset a group for example.' . PHP_EOL .
		'To start the script, type in a group name or all for all the backfill enabled groups.' . PHP_EOL
	);
}

require_once dirname(__FILE__) . '/../../../www/config.php';

$pdo = new \nzedb\db\DB();

if ($argv[1] === 'all') {
	$groups = $pdo->query('SELECT * FROM groups WHERE backfill = 1');
} else {
	$groups = $pdo->query(sprintf('SELECT * FROM groups WHERE name = %s', $pdo->escapeString($argv[1])));
}

if (count($groups) === 0) {
	if ($argv[1] === 'all') {
		exit ('ERROR! No groups were found with backfill enabled!' . PHP_EOL);
	} else {
		exit ('ERROR! Group (' . $argv[1] . ') not found!' . PHP_EOL);
	}
}

$nntp = new \NNTP(['Settings' => $pdo]);

$nntp->doConnect() or exit('Could not connect to Usenet!' . PHP_EOL);

$binaries = new \Binaries(['NNTP' => $nntp, 'Settings' => $pdo]);

foreach ($groups as $group) {
	$groupNNTP = $nntp->selectGroup($group['name']);
	if ($nntp->isError($groupNNTP)) {
		echo 'ERROR! Could not fetch information from NNTP for group (' . $group['name'] . ')' . PHP_EOL;
		continue;
	}

	$postDate = $pdo->queryOneRow(
		sprintf('SELECT UNIX_TIMESTAMP(postdate) AS postdate FROM releases WHERE group_id = %d ORDER BY postdate ASC LIMIT 1', $group['id'])
	);
	if ($postDate === false) {
		echo 'ERROR! Could not find any existing releases for group (' . $group['name'] . ')' . PHP_EOL;
		continue;
	}

	$articleNumber = (int)$binaries->daytopost(round(((time() - $postDate['postdate']) / 86400)), $groupNNTP);
	if ($group['last_record'] != 0 && $articleNumber >= $group['last_record']) {
		echo 'ERROR! Could not determine the article number for this date: (' .
			$postDate['postdate'] . ') on group (' . $group['name'] . ')' . PHP_EOL;
		continue;
	}

	$articleDate = $binaries->postdate($articleNumber, $groupNNTP);

	$pdo->queryExec(
		sprintf('
			UPDATE groups
			SET first_record = %d, first_record_postdate = %s
			WHERE id = %d',
			$articleNumber,
			$pdo->from_unixtime($articleDate),
			$group['id']
		)
	);

	echo
		'SUCCESS! Updated group (' . $group['name'] . ')\'s first article number to (' .
		$articleNumber . ') dated (' . date('r', $articleDate) . ').' . PHP_EOL .
		'The previous first article number was: (' . $group['first_record'] . ')' .
		(empty($group['first_record_postdate']) ? '.' : ' dated (' . date('r', strtotime($group['first_record_postdate'])) . ').') .
		PHP_EOL;
}
