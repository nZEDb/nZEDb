<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;
use nzedb\TVDB;

// Test if your TvDB API key and configuration are working.
// If it works you should get a var dumped array of the Walking Dead's first episode

$tvdb = new TVDB();

$serverTime = $tvdb->client->getServerTime();

// Search for a show
$data = $tvdb->client->getSeries('Walking Dead');

// Use the first show found and get the S01E01 episode
$episode = $tvdb->client->getEpisode($data[0]->id, 1, 1, 'en');
var_dump($episode);


