<?php
// --------------------------------------------------------------
//                  Manage sabnzbd v1.x via API
// --------------------------------------------------------------
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\db\DB;

$pdo = new DB();

if (!isset($argv[1])) {
	exit($pdo->log->header("Use this script to control sabnzbd via script.\n"
		. "Useful when bandwidth and/or connections are limited.\n\n"
		. "First paramater is a nZEDb username with a full sabnzbd API key\n"
		. "Second command paramater is either pause, resume or speedlimit\n"
		. "Third parameter is required for the speedlimit command\n"
		. "e.g.\n"
		. "php $argv[0] nZEDb-username pause [minutes]\n"
		. "php $argv[0] nZEDb-username resume\n"
		. "php $argv[0] nZEDb-username speedlimit 50    ...: To set the speed limit to 50%\n"));
}

$usersettings = $pdo->queryOneRow(sprintf("SELECT * FROM users WHERE username = %s ", $pdo->escapeString($argv[1])));
$saburl = $usersettings['saburl'];
$sabapikey = $usersettings['sabapikey'];
$sabapikeytype = $usersettings['sabapikeytype'];
if ($sabapikeytype != 2) {
	exit($pdo->log->error("\nnZEDb-username invalid or does not have full sabnzbd API Key.\n"));
}

// --- Pause ---
if ($argv[2] === "pause") {
	if (isset($argv[3])) {
		echo $pdo->log->header("Pausing sabnzbd for " . $argv[3] . " minutes");
		$response = file_get_contents($saburl . 'api?mode=config&name=set_pause&value=' . $argv[3] . '&apikey=' . $sabapikey);
	} else {
	echo $pdo->log->header("Pausing sabnzbd");
		$response = file_get_contents($saburl . 'api?mode=pause&apikey=' . $sabapikey);
	}
	echo $pdo->log->header($response);
}

// --- Resume ---
if ($argv[2] === "resume") {
	echo $pdo->log->header("Resuming sabnzbd");
	$response = file_get_contents($saburl . 'api?mode=resume&apikey=' . $sabapikey);
	echo $pdo->log->header($response);
}

// --- Speed Limit ---
if ($argv[2] === "speedlimit" && isset($argv[3]) && is_numeric($argv[3])) {
	echo $pdo->log->header("Speed limiting sabnzbd to " . $argv[3] . "%");
	$response = file_get_contents($saburl . "api?mode=config&name=speedlimit&value={$argv[3]}&apikey=$sabapikey");
	echo $pdo->log->header($response);
}
