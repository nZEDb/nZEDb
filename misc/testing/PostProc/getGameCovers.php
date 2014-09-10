<?php
//This script will update all records in the gamesinfo table
require_once dirname(__FILE__) . '/../../../www/config.php';

$pdo = new \nzedb\db\Settings();
$game = new \Games(['Echo' => true, 'Settings' => $pdo]);

$res = $pdo->query(
	sprintf("SELECT id, title FROM gamesinfo WHERE cover = 0 ORDER BY id DESC LIMIT 100")
);
$total = count($res);
if ($total > 0) {
	echo $pdo->log->header("Updating game covers for " . number_format($total) . " releases.");

	foreach ($res as $arr) {
		$starttime = microtime(true);
		$gameInfo = $game->parseTitle($arr['title']);
		if ($gameInfo !== false) {
			echo $pdo->log->primary('Looking up: ' . $gameInfo['release']);
			$gameData = $game->updateGamesInfo($gameInfo);
			if ($gameData === false) {
				echo $pdo->log->primary($gameInfo['release'] . ' not found');
			} else {
				if (file_exists(nZEDb_COVERS . 'games' . DS . $gameData . '.jpg')) {
					$pdo->queryExec(sprintf('UPDATE gamesinfo SET cover = 1 WHERE id = %d',	$arr['id']));
				}
			}
		}

		// amazon limits are 1 per 1 sec
		$diff = floor((microtime(true) - $starttime) * 1000000);
		if (1000000 - $diff > 0) {
			echo $pdo->log->alternate("Sleeping");
			usleep(1000000 - $diff);
		}
	}
}
