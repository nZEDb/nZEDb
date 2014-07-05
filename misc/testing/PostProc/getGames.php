<?php
//This script will update all records in the gamesinfo table
require_once dirname(__FILE__) . '/../../../www/config.php';

$game = new Games(true);
$pdo = new nzedb\db\DB();
$c = new ColorCLI();

$res = $pdo->query(
	sprintf("SELECT searchname FROM releases WHERE gamesinfo_id IS NULL AND categoryid = 4050 ORDER BY id DESC LIMIT 100")
);
$total = count($res);
if ($total > 0) {
	echo $c->header("Updating game info for " . number_format($total) . " releases.");

	foreach ($res as $arr) {
		$starttime = microtime(true);
		$gameInfo = $game->parseTitle($arr['searchname']);
		if ($gameInfo !== false) {
			$gameData = $game->updateGamesInfo($gameInfo);
			if ($gameData === false) {
				echo $c->primary($gameInfo['release'] . ' not found');
			}
		}

		// amazon limits are 1 per 1 sec
		$diff = floor((microtime(true) - $starttime) * 1000000);
		if (1000000 - $diff > 0) {
			echo $c->alternate("Sleeping");
			usleep(1000000 - $diff);
		}
	}
}
