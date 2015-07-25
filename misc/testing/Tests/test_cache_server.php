<?php
// Test the cache server connection.
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\libraries\Cache;

try {
	$cache = new Cache();
} catch (\Exception $error) {
	exit($error->getMessage() . PHP_EOL);
}

print_r($cache->serverStatistics());
