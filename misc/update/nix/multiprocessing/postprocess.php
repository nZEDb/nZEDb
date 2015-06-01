<?php
if (!isset($argv[1]) || !in_array($argv[1], ['ama', 'add', 'mov', 'nfo', 'sha', 'tv'])) {
	exit(
		'First argument (mandatory):' . PHP_EOL .
		'ama => Do amazon processing, this does not use multi-processing, because of amazon API restrictions.' . PHP_EOL .
		'add => Do additional (rar|zip) processing.' . PHP_EOL .
		'mov => Do movie processing.' . PHP_EOL .
		'nfo => Do NFO processing.' . PHP_EOL .
		'sha => Do sharing processing, this does not using multi-processing.' . PHP_EOL .
		'tv  => Do TV processing.' . PHP_EOL . PHP_EOL .
		'Second argument (optional):' . PHP_EOL .
		'true|false => Only post-process renamed releases. This is for the mov|tv options.' . PHP_EOL
	);
}

use nzedb\libraries\Forking;

declare(ticks = 1);

require('.do_not_run/require.php');

(new Forking())->processWorkType('postProcess_' . $argv[1], (isset($argv[2]) && $argv[2] === 'true' ? [0 => true] : []));
