<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\processing\tv\TVMaze;

$c = new nzedb\ColorCLI();
$tvmaze = new TVMaze();

if (isset($argv[1]) && !empty($argv[1]) && isset($argv[2]) && is_numeric($argv[2]) && isset($argv[3]) && is_numeric($argv[3])) {

	// Test if your TVMaze API configuration is working
	// If it works you should get a var dumped array of the show/season/episode entered

	$season = (int)$argv[2];
	$episode = (int)$argv[3];
	$day = (isset($argv[4]) && is_numeric($argv[4]) ? $argv[4] : '');

	// Search for a show
	$series = $tvmaze->client->search((string)$argv[1]);

	// Use the first show found (highest match) and get the requested season/episode from $argv
	if ($series) {

		print_r($series[0]);

		if ($season > 0 && $episode > 0 && $day === '') {
			$episodeObj = $tvmaze->client->getEpisodeByNumber($series[0]->id, $season, $episode);
			if ($episodeObj) {
				print_r($episodeObj);
			}
		} else if ($season == 0 && $episode == 0) {
			$episodeObj = $tvmaze->client->getEpisodesByShowID($series[0]->id);
			if (is_array($episodeObj)) {
				print_r($episodeObj);
			}
		} else if (preg_match('#^(19|20)\d{2}\/\d{2}\/\d{2}$#', $season . '/' . $episode . '/' . $day, $airdate)) {
			$episodeObj = $tvmaze->client->getEpisodesByAirdate($series[0]->id, (string)$airdate[0]);
			if ($episodeObj) {
				print_r($episodeObj);
			}
		} else {
			exit($c->error("Invalid episode data returned from TVMaze API."));
		}

	} else {
		exit($c->error("Invalid show data returned from TVMaze API."));
	}

} else {
	exit($c->error("Invalid arguments. This script requires a text string (show name) followed by a season and episode number." . PHP_EOL .
		"You can also optionally supply 'YYYY' 'MM' 'DD' arguments instead of season/episode for an airdate lookup.")
	);
}
