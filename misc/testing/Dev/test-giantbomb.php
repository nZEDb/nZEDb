<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'GiantBombAPI.php';

// Test if your giantbomb key is working.

$s  = new Sites();
$site = $s->get();
$giantbombkey = $site->giantbombkey;
$c = new ColorCLI();
$obj = new GiantBomb($giantbombkey, $resp = "json");

try {
	$result = $obj->search("South Park The Stick Of Truth", '', 1);
	$result = json_decode(json_encode($result), true);
	$gameid = $result['results'][0]['id'];
	$fields = array(
		"deck", "description", "original_game_rating", "api_detail_url", "image", "genres", "name",
		"platforms", "publishers", "original_release_date", "reviews", "site_detail_url"
	);
	$result = $obj->game($gameid, $fields);
	$result = json_decode(json_encode($result), true);
} catch (Exception $e) {
	$result = false;
}

if ($result !== false) {
	print_r($result);
	exit($c->header("\nLooks like it is working alright."));
} else {
	print_r($e);
	exit($c->error("\nThere was a problem attemtping to query amazon. Maybe your keys or wrong, or you are being throttled.\n"));
}
