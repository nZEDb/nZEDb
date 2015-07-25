<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ColorCLI;
use nzedb\NameFixer;

$cli = new ColorCLI();

if (!isset($argv[1]) && ($argv[1] !== 'full' || !is_numeric($argv[1]))) {
	exit(
		$cli->error(PHP_EOL
			. "This script tries to match release filenames to PreDB filenames." . PHP_EOL
			. "To display the changes, use 'show' as the second argument. The optional third argument will limit the amount of filenames to attempt to match." . PHP_EOL . PHP_EOL
			. "php match_prefiles.php full show	...: to run on full database and show renames." . PHP_EOL
			. "php match_prefiles.php 2000 show	...: to run against 2000 distinct releases and show renames." . PHP_EOL
		)
	);
}

$nameFixer = new NameFixer();

$nameFixer->getPreFileNames($argv);
