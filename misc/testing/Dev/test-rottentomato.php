<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

$c = new ColorCLI();

$rtkey = (new Settings())->getSetting('rottentomatokey');
if (isset($rtkey)) {
	$rt = new RottenTomato($rtkey);
	print_r(json_decode($rt->searchMovie("inception")));
	$url = (RottenTomato::API_URL . "movies.json?apikey=" . $rt->getApiKey() . "&q=inception&page_limit=50");
	exit($c->header("\nIf nothing was displayed above then there might be an error. If so, go to the following url: ".$url."\n"));
} else {
	exit($c->error("\nNo rotten tomato key.\n"));
}
