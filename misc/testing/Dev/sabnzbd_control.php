<?php
// --------------------------------------------------------------
//                  Manage sabnzbd via API
// --------------------------------------------------------------
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (!isset($argv[1])) {
	exit($pdo->log->error("\nUse this script to manage sabnzbd.\n"
		. "Useful when bandwidth and/or connections are limited.\n"
		. "A full API Key is required.\n"
		. "sabnzbd-command = pause, resume or speedlimit 1234 (where 1234 = KB/s)\n\n"
		. "php $argv[0] nZEDb-username [pause, resume speedlimit]\n"
		. "php $argv[0] nZEDb-username speedlimit 200    ...: To set the speed limit to 200 KB/s\n"));
}

$usersettings = $pdo->queryOneRow(sprintf("SELECT * FROM users WHERE LOWER(username) = LOWER(%s) ", $pdo->escapeString($argv[1])));
$saburl = $usersettings['saburl'];
$sabapikey = $usersettings['sabapikey'];
$sabapikeytype = $usersettings['sabapikeytype'];
if ($sabapikeytype != 2) {
	exit($pdo->log->error("\nnZEDb-username invalid or does not have full sabnzbd API Key.\n"));
}

// --- Pause ---
if ($argv[2] === "pause") {
	echo $pdo->log->header("Pausing sabnzbd.");
	$response = file_get_contents($saburl . 'api?mode=pause&apikey=' . $sabapikey);
	echo $pdo->log->header($response);
}

// --- Resume ---
if ($argv[2] === "resume") {
	echo $pdo->log->header("Resuming sabnzbd.");
	$response = file_get_contents($saburl . 'api?mode=resume&apikey=' . $sabapikey);
	echo $pdo->log->header($response);
}

// --- Speed Limit ---
if ($argv[2] === "speedlimit" && isset($argv[3]) && is_numeric($argv[3])) {
	echo $pdo->log->header("Speed limiting sabnzbd to " . $argv[3] . " KB/s");
	$response = file_get_contents($saburl . "api?mode=config&name=speedlimit&value={$argv[3]}&apikey=$sabapikey");
	echo $pdo->log->header($response);
}
