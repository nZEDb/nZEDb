<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;

$pdo = new Settings();
if (isset($argv[1]) && in_array($argv[1], ['space', 'analyze', 'full'])) {

	if ($argv[1] === 'analyze') {
		echo $pdo->log->header('Analyzing MySQL tables, this can take a while...' . PHP_EOL);
	} else {
		echo $pdo->log->header('Optimizing MySQL tables, should be quick...' . PHP_EOL);
	}
	$tableCount = $pdo->optimise(false, $argv[1], (isset($argv[2]) && $argv[2] === 'true'), (isset($argv[3]) ? [$argv[3]] : []));
	if ($tableCount > 0 && $argv[1] === 'analyze') {
		exit($pdo->log->header("Analyzed {$tableCount} MySQL tables successfully." . PHP_EOL));
	} else if ($tableCount > 0) {
		exit($pdo->log->header("Optimized {$tableCount} MySQL tables successfully." . PHP_EOL));
	} else {
		exit($pdo->log->notice('No MySQL tables to optimize.' . PHP_EOL));
	}
} else {
	exit($pdo->log->error(
			'This script will optimise the tables.' . PHP_EOL .
			'Argument 1:' . PHP_EOL .
			'space        ...: Optimise the tables that have free space > 5%.' . PHP_EOL .
			'full         ...: Force Optimise on all tables.' . PHP_EOL .
			'analyze      ...: Analyze tables to rebuild statistics.' . PHP_EOL . PHP_EOL .
			'Argument 2:' . PHP_EOL .
			'true|false   ...: (Optional) Work on local tables? (good for replication).' . PHP_EOL . PHP_EOL .
			'Argument 3:' . PHP_EOL .
			'Table Name   ...: (Optional) Name of a MySQL table, like releases' . PHP_EOL
		)
	);
}
