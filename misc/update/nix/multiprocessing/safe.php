<?php
if (!isset($argv[1]) || !in_array($argv[1], ['backfill', 'binaries'])) {
	exit(
		'First argument (mandatory):' . PHP_EOL .
		'binaries => Do Safe Binaries update.' . PHP_EOL .
		'backfill => Do Safe Backfill update.' . PHP_EOL
	);
}

require('.do_not_run/require.php');

use nzedb\libraries\Forking;

declare(ticks = 1)

(new Forking())->processWorkType('safe_' . $argv[1]);
