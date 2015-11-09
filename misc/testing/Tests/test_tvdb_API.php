<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\processing\tv\TVDB;

$c = new nzedb\ColorCLI();
$tvdb = new TVDB();

if (!empty($argv[1]) && is_numeric($argv[2]) && is_numeric($argv[3])) {

	// Test if your TvDB API key and configuration are working
	// If it works you should get a var dumped array of the show/season/episode entered

	$season = (int)$argv[2];
	$episode = (int)$argv[3];

	$serverTime = $tvdb->client->getServerTime();

	// Search for a show
	$series = $tvdb->client->getSeries((string)$argv[1]);

	// Use the first show found (highest match) and get the requested season/episode from $argv
	if ($series) {

		echo PHP_EOL . $c->info("Server Time: " . $serverTime) .  PHP_EOL;
		print_r($series[0]);

		if ($season > 0 AND $episode > 0) {
			$episodeObj = $tvdb->client->getEpisode($series[0]->id, $season, $episode, 'en');
			if ($episodeObj) {
				print_r($episodeObj);
			}
		} else if ($season == 0 && $episode == 0) {
			$episodeObj = $tvdb->client->getSerieEpisodes($series[0]->id, 'en');
			if (is_array($episodeObj['episodes'])) {
				foreach ($episodeObj['episodes'] AS $ep) {
					print_r($ep);
				}
			}
		} else {
			exit($c->error("Invalid episode data returned from TVDB API."));
		}

	} else {
		exit($c->error("Invalid show data returned from TVDB API."));
	}

} else {
	exit($c->error("Invalid arguments.  This script requires a text string (show name) followed by a season and episode number."));
}
