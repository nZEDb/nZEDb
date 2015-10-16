<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;
use nzedb\processing\tv\TraktTv;

$c = new nzedb\ColorCLI();
$trakt = new TraktTv();

if (!empty($argv[1]) && is_numeric($argv[2]) && is_numeric($argv[3])) {

	// Test if your Trakt API key and configuration are working
	// If it works you should get a printed array of the show/season/episode entered

	// Search for a show
	$series = $trakt->showSearch((string)$argv[1], 'show');

	// Use the first show found (highest match) and get the requested season/episode from $argv
	if (is_array($series)) {

		$episode = $trakt->episodeSummary($series[0]['show']['ids']['trakt'], (int)$argv[2], (int)$argv[3]);

		print_r($series[0]);
		print_r($episode);

	} else {
		exit($c->error("Error retrieving Trakt data."));
	}
} else {
	exit($c->error("Invalid arguments.  This script requires a text string (show name) followed by a season and episode number."));
}
