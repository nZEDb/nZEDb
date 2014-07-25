<?php
require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();

if (isset($argv[1]) && $argv[1] === "true") {
	getOddGames($c);
} else {
	exit($c->error("\nThis script attempts to recategorize 150 games each run in 0day and ISO that have a match on giantbomb.\n"
					. "php $argv[0] true       ...:recategorize 0day/ISO games.\n"));
}

function getOddGames($c)
{
	global $c;
	$pdo = new Settings();
	$res = $pdo->query('
				SELECT searchname, id, categoryid
				FROM releases
				WHERE nzbstatus = 1
				AND gamesinfo_id = 0
				AND categoryid BETWEEN 4010 AND 4020
				ORDER BY postdate DESC LIMIT 150'
	);

	if ($res !== false) {
				$c->doEcho($c->header("Processing... 150 release(s)."));
			$gen = new Games(['Echo' => true, 'Settings' => $pdo, 'ColorCLI' => $c]);

			//Match on 78% title
			$gen->matchpercent = 78;
			foreach ($res as $arr) {
				$startTime = microtime(true);
				$usedgb = true;
				$gameInfo = $gen->parseTitle($arr['searchname']);
				if ($gameInfo !== false) {
						$c->doEcho(
							$c->headerOver('Looking up: ') .
							$c->primary($gameInfo['title'] . ' (' . $gameInfo['platform'] . ')' )
						);

					// Check for existing games entry.
					$gameCheck = $gen->getgamesinfoByName($gameInfo['title'], $gameInfo['platform']);
					if ($gameCheck === false) {
						$gameId = $gen->updategamesinfo($gameInfo);
						$usedgb = true;
						if ($gameId === false) {
							$gameId = -2;

							//If result is empty then set gamesinfo_id back to 0 so we can parse it at a later time.
							if ($gen->maxhitrequest === true) {
								$gameId = 0;
							}
						}
					} else {
						$gameId = $gameCheck['id'];
					}
					if ($gameId != -2 && $gameId != 0) {
						$arr['categoryid'] = 4050;
					}

					$pdo->queryExec(sprintf('UPDATE releases SET gamesinfo_id = %d, categoryid = %d WHERE id = %d', $gameId, $arr['categoryid'], $arr['id']));
				} else {
					// Could not parse release title.
					$pdo->queryExec(sprintf('UPDATE releases SET gamesinfo_id = %d WHERE id = %d', -2, $arr['id']));
						echo '.';
				}
				// Sleep so not to flood giantbomb.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($gen->sleeptime * 1000 - $diff > 0 && $usedgb === true) {
					usleep($gen->sleeptime * 1000 - $diff);
				}
			}
		} else {
				$c->doEcho($c->header('No games in 0day/ISO to process.'));
		}
	}
