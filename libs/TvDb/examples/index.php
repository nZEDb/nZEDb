<?php
include __DIR__ . '/settings.php';
include __DIR__ . '/../src/Moinax/TvDb/Http/HttpClient.php';
include __DIR__ . '/../src/Moinax/TvDb/Http/CurlClient.php';
include __DIR__ . '/../src/Moinax/TvDb/CurlException.php';
include __DIR__ . '/../src/Moinax/TvDb/Client.php';
include __DIR__ . '/../src/Moinax/TvDb/Serie.php';
include __DIR__ . '/../src/Moinax/TvDb/Banner.php';
include __DIR__ . '/../src/Moinax/TvDb/Episode.php';

use Moinax\TvDb\Client;

$tvdb = new Client(TVDB_URL, TVDB_API_KEY);

$serverTime = $tvdb->getServerTime();
// Search for a show
$data = $tvdb->getSeries('Walking Dead');

// Use the first show found and get the S01E01 episode
$episode = $tvdb->getEpisode($data[0]->id, 1, 1, 'en');
var_dump($episode);

/*$date = new \DateTime('-1 day');
$data = $tvdb->getUpdates($date->getTimestamp());
var_dump($data);
*/

/*
// Get full series and episode info
$episodes = $tvdb->getSerieEpisodes(153021, 'fr', Client::FORMAT_ZIP);
var_dump($episodes["episodes"]);
printf ("(%d Episodes)\n", count($episodes["episodes"]));
*/
