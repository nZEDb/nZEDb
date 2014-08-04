<?php
require_once dirname(__FILE__) . '/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
if (isset($argv[1]) && ($argv[1] === "run" || $argv[1] === "true" || $argv[1] === "all" || $argv[1] === "full" || $argv[1] === "analyze")) {

	if ($argv[1] === 'analyze') {
		echo $pdo->log->header("Analyzing MySQL tables, this can take a while...");
	} else {
		echo $pdo->log->header("Optimizing MySQL tables, should be quick...");
	}
	$tablecnt = $pdo->optimise(false, $argv[1]);
	if ($tablecnt > 0 && $argv[1] === 'analyze') {
		exit($pdo->log->header("\nAnalyzed {$tablecnt} MySQL tables successfully."));
	} else if ($tablecnt > 0) {
		exit($pdo->log->header("\nOptimized {$tablecnt} MySQL tables successfully."));
	} else {
		exit($pdo->log->notice("\nNo MySQL tables to optimize."));
	}
} else {
	exit($pdo->log->error("\nThis script will optimise the tables.\n\n"
		. "php $argv[0] run|all      ...: Optimise the tables that have freespace > 5%.\n"
		. "php $argv[0] true|full    ...: Force Optimise on all tables.\n"
		. "php $argv[0] analyze      ...: Analyze tables to rebuild statistics.\n"));
}
