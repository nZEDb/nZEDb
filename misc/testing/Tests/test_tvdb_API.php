<?php

require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');
//require_once nZEDb_LIBS . 'AmazonProductAPI.php';

use nzedb\db\Settings;

// Test if your TvDB API key and configuration are working.
// If it works you should get a var dumped array of the Walking Dead's first episode

$tvdb = (new TVDB())->Client(TVDB::TVDB_URL, TVDB::TVDB_API_KEY);

$serverTime = $tvdb->getServerTime();

// Search for a show
$data = $tvdb->getSeries('Walking Dead');

// Use the first show found and get the S01E01 episode
$episode = $tvdb->getEpisode($data[0]->id, 1, 1, 'en');
var_dump($episode);


