<?php
require_once dirname(__FILE__) . '/config.php';

$c = new ColorCLI();

if (!isset($argv[1]) || ( $argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1]))) {
	exit ($c->error(
			PHP_EOL
			. "This script tries to match a release request ID by group to a PreDB request ID by group doing local lookup only." . PHP_EOL
			. "In addition an optional final argument is time, in minutes, to check releases that have previously been checked." . PHP_EOL . PHP_EOL
			. "php requestid.php 1000 show		...: to limit to 1000 sorted by newest postdate and show renaming." . PHP_EOL
			. "php requestid.php full show		...: to run on full database and show renaming." . PHP_EOL
			. "php requestid.php all show		...: to run on all requestid releases (including previously renamed) and show renaming." . PHP_EOL
			)
		);
}

use nzedb\db\Settings;
$reqidlocal = new ReqIDLocal($c);

$reqidlocal->standaloneLocalLookup($argv);
exit;