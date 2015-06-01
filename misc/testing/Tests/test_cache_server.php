<?php
// Test the cache server connection.
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\libraries\Cache;

try {
	$cache = new Cache();
} catch (\Exception $error) {
	exit($error->getMessage() . PHP_EOL);
}

print_r($cache->serverStatistics());
