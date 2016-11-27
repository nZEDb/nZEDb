<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\processing\tv\TMDB;

$c = new nzedb\ColorCLI();
$tmdb = new TMDB();

if (!empty($argv[1]) && is_numeric($argv[2]) && is_numeric($argv[3])) {

	// Test if your TMDB API configuration is working
	// If it works you should get a var dumped array of the show/season/episode entered

	$season = (int)$argv[2];
	$episode = (int)$argv[3];

	// Search for a show
	$series = $tmdb->client->searchTVShow((string)$argv[1]);

	// Use the first show found (highest match) and get the requested season/episode from $argv
	if ($series) {
		$seriesAppends = $tmdb->client->getTVShow($series[0]->_data['id'], 'append_to_response=alternative_titles,external_ids');
		if ($seriesAppends) {
			$series[0]->_data['networks'] = $seriesAppends->_data['networks'];
			$series[0]->_data['alternative_titles'] = $seriesAppends->_data['alternative_titles']['results'];
			$series[0]->_data['external_ids'] = $seriesAppends->_data['external_ids'];
		}

		print_r($series[0]);

		if ($season > 0 && $episode > 0) {
			$episodeObj = $tmdb->client->getEpisode($series[0]->_data['id'], $season, $episode);
			if ($episodeObj) {
				print_r($episodeObj);
			}
		} else if ($season == 0 && $episode == 0) {
			$episodeObj = $tmdb->client->getTVShow($series[0]->_data['id']);
			if (is_array($episodeObj)) {
				foreach ($episodeObj as $ep) {
					print_r($ep);
				}
			}
		} else {
			exit($c->error("Invalid episode data returned from TMDB API."));
		}

	} else {
		exit($c->error("Invalid show data returned from TMDB API."));
	}

} else {
	exit($c->error("Invalid arguments.  This script requires a text string (show name) followed by a season and episode number."));
}
