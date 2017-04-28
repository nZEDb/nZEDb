<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\db\populate\AniDB;
use nzedb\ColorCLI;
use nzedb\db\DB;

$pdo = new DB();

if ($argc > 1 && $argv[1] === 'true' && isset($argv[2])) {
	if($argv[2] === 'full') {
		(new AniDB(['Settings' => $pdo, 'Echo' => true]))->populateTable('full');
	} elseif ($argv[2] === 'info'){
		if ($argv[3] !== null && is_numeric($argv[3])) {
			(new AniDB(['Settings' => $pdo, 'Echo' => true]))->populateTable('info', $argv[3]);
		} else {
			(new AniDB(['Settings' => $pdo, 'Echo' => true]))->populateTable('info');
		}
	}
} else {
	ColorCLI::doEcho(PHP_EOL . ColorCLI::error(
			'To execute this script you must provide a boolean argument.' . PHP_EOL .
			'Argument1: true|false to run this script or not' . PHP_EOL .
			'Argument2: full|info for what type of data to populate.' . PHP_EOL .
			'Argument3 (optional) anidbid to fetch info for' . PHP_EOL .
			ColorCLI::warning('Argument "info" without third argument will get you banned from AniDB almost instantly')), true
	);
}
