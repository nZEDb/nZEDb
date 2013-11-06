<?php
// --------------------------------------------------------------
//                  Manage sabnzbd via API
// --------------------------------------------------------------

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

if (!isset($argv[1]))
	exit("Use this script to manage sabnzbd.\nUseful when bandwidth and/or connections are limited.\nA full API Key is required. To run: \nphp sabnzbd_control.php nZEDb-username sabnzbd-command\nsabnzbd-command = pause, resume or speedlimit 1234 (where 1234 = KB/s)\n");

$db = new DB();
$usersettings = $db->queryOneRow(sprintf("select * FROM users WHERE LOWER(username) = LOWER(%s) ", $db->escapeString($argv[1])));
$saburl = $usersettings['saburl'];
$sabapikey = $usersettings['sabapikey'];
$sabapikeytype = $usersettings['sabapikeytype'];
if ($sabapikeytype != 2)
	exit("Error, nZEDb-username invalid or does not have full sabnzbd API Key.\n");

// --- Pause ---
if ($argv[2] === "pause")
{
	echo "Pausing sabnzbd.\n";
	$response = file_get_contents($saburl."api?mode=pause"."&apikey=".$sabapikey);
	echo $response;
}

// --- Resume ---
if ($argv[2] === "resume")
{
	echo "Resuming sabnzbd.\n";
	$response = file_get_contents($saburl."api?mode=resume"."&apikey=".$sabapikey);
	echo $response;
}
// --- Speed Limit ---
if ($argv[2] === "speedlimit" && isset($argv[3]) && is_numeric($argv[3]))
{
	echo "Speed limiting sabnzbd to ".$argv[3]." KB/s\n";
	$response = file_get_contents($saburl."api?mode=config&name=speedlimit&value=".$argv[3]."&apikey=".$sabapikey);
	echo $response;
}
?>
