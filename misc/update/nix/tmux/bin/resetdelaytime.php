<?php
require_once realpath(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use app\models\Settings;
use nzedb\db\DB;

$pdo = new DB();

$delaytimet = Settings::value('..delaytime');
$delaytimet = ($delaytimet) ? (int)$delaytimet : 2;

//reset collections past interval of creation to now
$tables = $pdo->queryDirect("SHOW TABLE STATUS");
$ran = 0;

foreach ($tables as $row) {
	if (preg_match('/(multigroup\_)?collections(_\d+)?/', $row['name'])) {
		$run = $pdo->queryExec(
			"UPDATE {$row['name']}
			SET dateadded = NOW()
			WHERE dateadded < (NOW() - INTERVAL {$delaytimet} HOUR)"
		);
		if ($run !== false) {
			$ran += $run->rowCount();
		}
	}
}
echo $pdo->log->primary(number_format($ran) . " collections reset.");

sleep(2);