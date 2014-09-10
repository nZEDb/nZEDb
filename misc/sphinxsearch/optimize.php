<?php
require dirname(__FILE__) . '/../../www/config.php';
if (!isset($argv[1]) || !in_array($argv[1], ['releases_rt'])) {
	exit('Argument1 is the index name, currently only releases_rt is supported.' . PHP_EOL);
}
(new \SphinxSearch())->optimizeRTIndex($argv[1]);
