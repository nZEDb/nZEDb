<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
use nzedb\db\Settings;

$cli = new \ColorCLI();

$rtkey = (new Settings())->getSetting('rottentomatokey');
if (isset($rtkey)) {
	$rt = new \RottenTomato($rtkey);
	print_r(json_decode($rt->searchMovie("inception")));
	$url = (\RottenTomato::API_URL . "movies.json?apikey=" . $rt->getApiKey() . "&q=inception&page_limit=50");
	exit($cli->header("\nIf nothing was displayed above then there might be an error. If so, go to the following url: ".$url."\n"));
} else {
	exit($cli->error("\nNo rotten tomato key.\n"));
}
