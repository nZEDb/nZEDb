<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$start = TIME();
$pdo = new Settings();
$consoleTools = new \ConsoleTools(['ColorCLI' => $pdo->log]);

// Create the connection here and pass
$nntp = new \NNTP(['Settings' => $pdo]);
if ($nntp->doConnect() !== true) {
	exit($pdo->log->error("Unable to connect to usenet."));
}

echo $pdo->log->header("Getting first/last for all your active groups.");
$data = $nntp->getGroups();
if ($nntp->isError($data)) {
	exit($pdo->log->error("Failed to getGroups() from nntp server."));
}

echo $pdo->log->header("Inserting new values into shortgroups table.");

$pdo->queryExec('TRUNCATE TABLE shortgroups');

// Put into an array all active groups
$res = $pdo->query('SELECT name FROM groups WHERE active = 1 OR backfill = 1');

foreach ($data as $newgroup) {
	if (myInArray($res, $newgroup['group'], 'name')) {
		$pdo->queryInsert(sprintf('INSERT INTO shortgroups (name, first_record, last_record, updated) VALUES (%s, %s, %s, NOW())', $pdo->escapeString($newgroup['group']), $pdo->escapeString($newgroup['first']), $pdo->escapeString($newgroup['last'])));
		echo $pdo->log->primary('Updated ' . $newgroup['group']);
	}
}
echo $pdo->log->header('Running time: ' . $consoleTools->convertTimer(TIME() - $start));

function myInArray($array, $value, $key)
{
	//loop through the array
	foreach ($array as $val) {
		//if $val is an array cal myInArray again with $val as array input
		if (is_array($val)) {
			if (myInArray($val, $value, $key)) {
				return true;
			}
		} else {
			//else check if the given key has $value as value
			if ($array[$key] == $value) {
				return true;
			}
		}
	}
	return false;
}
