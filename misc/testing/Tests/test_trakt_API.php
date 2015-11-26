<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\processing\tv\TraktTv;

$c = new nzedb\ColorCLI();
$trakt = new TraktTv();

if (!empty($argv[1]) && is_numeric($argv[2]) && is_numeric($argv[3])) {

	// Test if your Trakt API key and configuration are working
	// If it works you should get a printed array of the show/season/episode entered

	// Search for a show
	$series = $trakt->client->showSearch((string)$argv[1], 'show');

	// Use the first show found (highest match) and get the requested season/episode from $argv
	if (is_array($series)) {

		$series = $trakt->client->showSummary($series[0]['show']['ids']['trakt'], 'full,images');
		$episode = $trakt->client->episodeSummary($series['ids']['trakt'], (int)$argv[2], (int)$argv[3], 'full');

		print_r($series);
		print_r($episode);

	} else {
		exit($c->error("Error retrieving Trakt data."));
	}
} else {
	exit($c->error("Invalid arguments.  This script requires a text string (show name) followed by a season and episode number."));
}
