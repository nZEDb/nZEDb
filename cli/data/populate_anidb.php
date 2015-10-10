<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\populate\AniDB;
use nzedb\db\Settings;

$pdo = new Settings();

if ($argc > 1 && $argv[1] == true) {
	(new AniDB(['Settings' => $pdo, 'Echo' => true]))->populateTable('full');
} else {
	$pdo->log->doEcho(PHP_EOL . $pdo->log->error(
				"To execute this script you must provide a boolean argument." . PHP_EOL .
				"Argument1: true|false to run this script or not"), true
	);
}
