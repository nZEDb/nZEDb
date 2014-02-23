<?php

//This script will update all records in the consoleinfo table

require_once dirname(__FILE__) . '/../../../www/config.php';

$console = new Console(true);
$db = new Db();
$c = new ColorCLI();

$res = $db->queryDirect(sprintf("SELECT searchname, id FROM releases WHERE consoleinfoid IS NULL AND categoryid IN ( SELECT id FROM category WHERE parentid = %d ) ORDER BY id DESC", Category::CAT_PARENT_GAME));
if ($res->rowCount() > 0) {
	echo $c->header("Updating console info for " . number_format($res->rowCount()) . " releases.");

	foreach ($res as $arr) {
		$starttime = microtime(true);
		$gameInfo = $console->parseTitle($arr['searchname']);
		if ($gameInfo !== false) {
			$game = $console->updateConsoleInfo($gameInfo);
			if ($game === false) {
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
