<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'GiantBombAPI.php';

// Test if your giantbomb key is working.

$giantbombkey = (new \nzedb\db\Settings())->getSetting('giantbombkey');
$cli = new \ColorCLI();
$obj = new \GiantBomb($giantbombkey, $resp = "json");

$searchgame = "South Park The Stick of Truth";
$resultsfound = 0;

$e = null;
try {
	$fields = array(
		"deck", "description", "original_game_rating", "api_detail_url", "image", "genres", "name",
		"platforms", "publishers", "original_release_date", "reviews", "site_detail_url"
	);
	$result = $obj->search($searchgame, $fields, 1);
	$result = json_decode(json_encode($result), true);
	if ($result['number_of_total_results'] != 0) {
		$resultsfound = count($result['results']);
		for ($i = 0; $i <= $resultsfound; $i++) {
			similar_text($result['results'][$i]['name'], $searchgame, $p);
			if ($p > 90) {
				$result = $result['results'][$i];
				break;
			}
		}
	}
} catch (Exception $e) {
	$result = false;
}

if ($result !== false && !empty($result)) {
	print_r($result);
	exit($cli->header("\nLooks like it is working alright."));
} else {
	print_r($e);
	exit($cli->error("\nThere was a problem attempting to query giantbomb. Maybe your key is wrong, or you are being throttled.\n"));
}
