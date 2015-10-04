<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;
use nzedb\TVDB;

$c = new nzedb\ColorCLI();
$tvdb = new TVDB();

if (!empty($argv[1]) && is_numeric($argv[2]) && is_numeric($argv[3])) {

	// Test if your TvDB API key and configuration are working
	// If it works you should get a var dumped array of the show/season/episode entered

	$serverTime = $tvdb->client->getServerTime();

	// Search for a show
	$data = $tvdb->client->getSeries((string)$argv[1]);

	// Use the first show found and get the requested season/episode from $argv

	echo PHP_EOL . $c->info("Server Time: " . $serverTime) .  PHP_EOL;

	$episode = $tvdb->client->getEpisode($data[0]->id, (int)$argv[2], (int)$argv[3], 'en');
	
	print_r($episode);

} else {
	exit($c->error("Invalid arguments.  This script requires a text string (show name) followed by a season and episode number."));
}