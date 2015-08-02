<?php
if (!isset($argv[1]) || !is_numeric($argv[1])) {
	exit('Argument 1 => (Number) Set to 0 to ignore, else fetches up to x new headers for every active group.' . PHP_EOL);
}

require('.do_not_run/require.php');

use nzedb\libraries\Forking;

declare(ticks = 1);

(new Forking())->processWorkType('binaries', [0 => $argv[1]]);
