<?php

require_once realpath(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ConsoleTools;

// This script is simply so I can show sleep progress in bash script
$consoletools = new ConsoleTools();
if (isset($argv[1]) && is_numeric($argv[1])) {
	$consoletools->showsleep($argv[1]);
}
