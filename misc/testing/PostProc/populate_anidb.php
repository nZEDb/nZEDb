<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use \nzedb\db\Settings;

$pdo = new Settings();

if ($argc > 1 && $argv[1] == true) {
	(new \nzedb\db\populate\AniDB(['Settings' => $pdo, 'Echo' => true]))->populateTable('full');
} else {
	$pdo->log->doEcho(PHP_EOL . $pdo->log->error(
				"To execute this script you must provide a boolean argument." . PHP_EOL .
				"Argument1: true|false to run this script or not"), true
	);
}
