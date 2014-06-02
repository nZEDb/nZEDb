<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

$s = new Sites();
$site = $s->get();
$c = new ColorCLI();

if (isset($site->rottentomatokey)) {
	$rt = new RottenTomato($site->rottentomatokey);
	print_r(json_decode($rt->searchMovie("inception")));
	$url = (RottenTomato::API_URL . "movies.json?apikey=" . $rt->getApiKey() . "&q=inception&page_limit=50");
	exit($c->header("\nIf nothing was displayed above then there might be an error. If so, go to the following url: ".$url."\n"));
} else {
	exit($c->error("\nNo rotten tomato key.\n"));
}
