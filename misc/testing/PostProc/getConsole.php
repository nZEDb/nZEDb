<?php
//This script will update all records in the consoleinfo table
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$console = new \Console(['Echo' => true, 'Settings' => $pdo]);

$res = $pdo->queryDirect(sprintf("SELECT searchname, id FROM releases WHERE consoleinfoid IS NULL AND categoryid BETWEEN 1000 AND 1999 ORDER BY id DESC" ));
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
