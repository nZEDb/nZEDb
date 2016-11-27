<?php
//This script will update all records in the consoleinfo table
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\Category;
use nzedb\Console;
use nzedb\db\DB;

$category = new Category();
$pdo = new DB();
$console = new Console(['Echo' => true, 'Settings' => $pdo]);

$res = $pdo->queryDirect(
		sprintf(
				"SELECT searchname, id FROM releases WHERE consoleinfo_id IS NULL AND categories_id
				BETWEEN %s AND %s ORDER BY id DESC",
				Category::GAME_ROOT,
				Category::GAME_OTHER
				));
if ($res instanceof \Traversable) {
	echo $pdo->log->header("Updating console info for " . number_format($res->rowCount()) . " releases.");

	foreach ($res as $arr) {
		$starttime = microtime(true);
		$gameInfo = $console->parseTitle($arr['searchname']);
		if ($gameInfo !== false) {
			$game = $console->updateConsoleInfo($gameInfo);
			if ($game === false) {
				echo $pdo->log->primary($gameInfo['release'] . ' not found');
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
