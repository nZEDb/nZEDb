<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'GiantBombAPI.php';

// Test if your giantbomb key is working.

$giantbombkey = (new \nzedb\db\Settings())->getSetting('giantbombkey');
$cli = new ColorCLI();
$obj = new GiantBomb($giantbombkey, $resp = "json");

$e = null;

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
	exit($cli->header("\nLooks like it is working alright."));
} else {
	print_r($e);
	exit($cli->error("\nThere was a problem attemtping to query amazon. Maybe your keys or wrong, or you are being throttled.\n"));
}
