<?php
if (!isset($argv[1]) || !in_array($argv[1], ['standard', 'predbft'])) {
	exit(
		'First argument (mandatory):' . PHP_EOL .
		'standard => Attempt to fix release name using standard methods.' . PHP_EOL .
		'predbft  => Attempt to fix release name using Predb full text matching.' . PHP_EOL . PHP_EOL
	);
}

require('.do_not_run/require.php');

use nzedb\libraries\Forking;

declare(ticks = 1);

(new Forking())->processWorkType('fixRelNames_' . $argv[1], [0 => $argv[1]]);
