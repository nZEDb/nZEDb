<?php
require_once dirname(__FILE__) . '/config.php';

$cli = new \ColorCLI();

if (!isset($argv[1]) || ($argv[1] != "all" && $argv[1] != "full" && $argv[1] != "web" && !is_numeric($argv[1])) || !isset($argv[2]) || !in_array($argv[2], ['true', 'false'])) {
	exit ($cli->error(
			PHP_EOL
			. "This script tries to match a release request ID by group to a PreDB request ID by group doing local lookup only." . PHP_EOL
			. "In addition an optional final argument is time, in minutes, to check releases that have previously been checked." . PHP_EOL . PHP_EOL
			. "Argument 1: full|all|number|web => (mandatory)" . PHP_EOL
			. "all does only requestid releases, full does full database, number limits to x amount of releases, web does web requestID's" . PHP_EOL
			. "Argument 2: true|false          => (mandatory) Display full info on how the release was renamed or not." . PHP_EOL
			. "Argument 3: number             => (optional)  This is to limit how old the releases to work on (in hours)." . PHP_EOL
			. "php requestid.php 1000 true    => to limit to 1000 sorted by newest postdate and show renaming." . PHP_EOL . PHP_EOL
			. "php requestid.php full true    => to run on full database and show renaming." . PHP_EOL
			. "php requestid.php all true     => to run on all requestid releases (including previously renamed) and show renaming." . PHP_EOL
			)
		);
}

if ($argv[1] === 'web') {
	(new \RequestIDWeb())->lookupRequestIDs(
		['limit' => $argv[1], 'show' => $argv[2], 'time' => (isset($argv[3]) && is_numeric($argv[3]) && $argv[3] > 0 ? $argv[3] : 0)]
	);
} else {
	(new \RequestIDLocal())->lookupRequestIDs(
		['limit' => $argv[1], 'show' => $argv[2], 'time' => (isset($argv[3]) && is_numeric($argv[3]) && $argv[3] > 0 ? $argv[3] : 0)]
	);
}
