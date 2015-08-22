<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\SphinxSearch;

if (!isset($argv[1]) || !in_array($argv[1], ['releases_rt', 'release_files_rt'])) {
	exit('Argument1 is the index name, currently only releases_rt/release_files_rt are supported.' . PHP_EOL);
}

(new SphinxSearch())->optimizeRTIndex($argv[1]);
