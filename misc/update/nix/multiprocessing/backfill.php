<?php
declare(ticks=1);
require(dirname(__FILE__) . '/../../config.php');
if (is_file('settings.php')) {
	require('settings.php');
}
// Check if argument 1 is numeric, which is to limit article count.
(new \nzedb\libraries\Forking())->processWorkType(
	'backfill', (isset($argv[1]) && is_numeric($argv[1]) && $argv[1] > 0 ? array(0 => $argv[1]) : array(0 => false))
);