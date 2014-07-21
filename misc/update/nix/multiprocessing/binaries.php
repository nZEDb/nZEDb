<?php
if (!isset($argv[1]) || !is_numeric($argv[1])) {
	exit(
		'Argument 1 => (Number) Set to 0 to ignore, else fetches up to x new headers for every active group.' . PHP_EOL
	);
}
declare(ticks=1);
require(dirname(__FILE__) . '/../../config.php');
if (is_file('settings.php')) {
	require('settings.php');
}
(new \nzedb\libraries\Forking())->processWorkType('binaries', array(0 => $argv[1]));