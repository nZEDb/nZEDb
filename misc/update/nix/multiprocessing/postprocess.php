<?php
if (!isset($argv[1])) {
	exit(
		'Available options:' . PHP_EOL .
		'all => Do all the types of post processing.' . PHP_EOL .
		'ama => Do amazon processing, this does not use multi-processing, because of amazon API restrictions.' . PHP_EOL .
		'add => Do additional (rar|zip) processing.' . PHP_EOL .
		'mov => Do movie processing.' . PHP_EOL .
		'nfo => Do NFO processing.' . PHP_EOL .
		'sha => Do sharing processing, this does not using multi-processing.' . PHP_EOL .
		'tv  => Do TV processing.' . PHP_EOL
	);
}

declare(ticks=1);
require(dirname(__FILE__) . '/../../config.php');
(new \nzedb\libraries\Forking())->processWorkType('postprocess', array(0 => $argv[1]));