<?php
if (!isset($argv[1]) || !in_array($argv[1], ['nfo', 'filename', 'md5', 'par2', 'miscsorter', 'predbft'])) {
	exit(
		'First argument (mandatory):' . PHP_EOL .
		'nfo => Attempt to fix release name using the nfo.' . PHP_EOL .
		'filename => Attempt to fix release name using the filenames.' . PHP_EOL .
		'md5 => Attempt to fix release name using the MD5.' . PHP_EOL .
		'par2 => Attempt to fix release name using the par2.' . PHP_EOL .
		'miscsorter => Attempt to fix release name using magic.' . PHP_EOL .
		'predbft  => Attempt to fix release name using Predb full text matching.' . PHP_EOL . PHP_EOL
	);
}

require('.do_not_run/require.php');

use nzedb\libraries\Forking;

declare(ticks = 1);

(new Forking())->processWorkType('fixRelNames_' . $argv[1], [0 => $argv[1]]);
