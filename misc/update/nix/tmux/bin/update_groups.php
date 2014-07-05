<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$start = TIME();
$pdo = new Settings();
$c = new ColorCLI();
$consoleTools = new ConsoleTools();

$nntpProxy = $pdo->getSetting('nntpproxy');

// Create the connection here and pass
$nntp = new NNTP();
if ($nntp->doConnect() !== true) {
	exit($c->error("Unable to connect to usenet."));
}
if ($nntpProxy == "1") {
	usleep(500000);
}

echo $c->header("Getting first/last for all your active groups.");
$data = $nntp->getGroups();
if ($nntp->isError($data)) {
	exit($c->error("Failed to getGroups() from nntp server."));
}

if ($nntpProxy != "1") {
	$nntp->doQuit();
}

echo $c->header("Inserting new values into shortgroups table.");

$pdo->queryExec('TRUNCATE TABLE shortgroups');

// Put into an array all active groups
$res = $pdo->query('SELECT name FROM groups WHERE active = 1 OR backfill = 1');

foreach ($data as $newgroup) {
	if (myInArray($res, $newgroup['group'], 'name')) {
		$pdo->queryInsert(sprintf('INSERT INTO shortgroups (name, first_record, last_record, updated) VALUES (%s, %s, %s, NOW())', $pdo->escapeString($newgroup['group']), $pdo->escapeString($newgroup['first']), $pdo->escapeString($newgroup['last'])));
		echo $c->primary('Updated ' . $newgroup['group']);
	}
}
echo $c->header('Running time: ' . $consoleTools->convertTimer(TIME() - $start));

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
