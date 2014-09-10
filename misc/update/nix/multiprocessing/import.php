<?php
declare(ticks=1);
require('.do_not_run/require.php');

if (!isset($argv[1]) || !is_dir($argv[1])) {
	exit(
		'First argument (mandatory):' . PHP_EOL .
		'Path to a folder, containing folders with .nzb or .nzb.gz files inside them.' . PHP_EOL .
		'If you supply a path containing only files, the files will be ignored.' . PHP_EOL .
		'The sub-folders will be searched recursively for NZB files.' . PHP_EOL . PHP_EOL .
		'Second argument (optional):' . PHP_EOL .
		'Number of processes, how many processes to run max at a time. (default is 1)' . PHP_EOL . PHP_EOL .
		'Third argument (optional):' . PHP_EOL .
		'true|false => Delete the NZB files after they are imported (recommended), if you stop and restart you will have to go over the imported files again.' . PHP_EOL . PHP_EOL .
		'Fourth argument (optional)' . PHP_EOL .
		'true|false => Delete the NZB if importing it fails (not recommended).' . PHP_EOL .
		'Fifth argument (optional):' . PHP_EOL .
		'true|false => Use the NZB file name as the release name (not recommended), the names in the NZB are better.' . PHP_EOL . PHP_EOL .
		'Sixth argument (optional):' . PHP_EOL .
		'How many NZB files to import per process, if this is not set, it will do 50,000 per process.' . PHP_EOL . PHP_EOL .
		'Note that successfully imported NZB files WILL be deleted.' . PHP_EOL

	);
}
(new \nzedb\libraries\ForkingImportNZB())->start(
	$argv[1],
	(isset($argv[2]) && is_numeric($argv[2]) && $argv[2] > 0 ? $argv[2] : 1),
	(isset($argv[3]) && $argv[3] === 'true' ? 'true' : 'false'),
	(isset($argv[4]) && $argv[4] === 'true' ? 'true' : 'false'),
	(isset($argv[5]) && $argv[5] === 'true' ? 'true' : 'false'),
	(isset($argv[6]) && is_numeric($argv[6]) && $argv[6] > 0 ? $argv[6] : 50000)
);
