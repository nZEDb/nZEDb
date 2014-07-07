<?php
require_once dirname(__FILE__) . '/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
$pdo = new Settings();
$type = $pdo->dbSystem();
if (isset($argv[1]) && ($argv[1] === "run" || $argv[1] === "true" || $argv[1] === "all" || $argv[1] === "full" || $argv[1] === "analyze")) {
	if ($type == 'mysql') {
		$a = 'MySQL';
		$b = 'Optimizing';
		$d = 'Optimized';
	} else if ($type == 'pgsql') {
		$a = 'PostgreSQL';
		$b = 'Vacuuming';
		$d = 'Vacuumed';
	}
	$e = 'Analyzed';
	$f = 'Analyzing';

	if ($argv[1] === 'analyze') {
		echo $c->header($f." ".$a." tables, this can take a while...");
	} else {
		echo $c->header($b." ".$a." tables, should be quick...");
	}
	$tablecnt = $pdo->optimise(false, $argv[1]);
	if ($tablecnt > 0 && $argv[1] === 'analyze') {
		exit($c->header("\n{$e} {$tablecnt} {$a} tables successfully."));
	} else if ($tablecnt > 0) {
		exit($c->header("\n{$d} {$tablecnt} {$a} tables successfully."));
	} else {
		exit($c->notice("\nNo {$a} tables to optimize."));
	}
} else {
	exit($c->error("\nThis script will optimise the tables.\n\n"
		. "php $argv[0] run          ...: Optimise the tables that have freespace > 5%.\n"
		. "php $argv[0] true         ...: Force Optimise on all tables.\n"
		. "php $argv[0] all          ...: Optimise all tables at once that have freespace > 5%.\n"
		. "php $argv[0] full         ...: Force Optimise all tables at once.\n"
		. "php $argv[0] analyze      ...: Analyze tables to rebuild statistics.\n"));
}
