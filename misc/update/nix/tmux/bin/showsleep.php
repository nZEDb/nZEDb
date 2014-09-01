<?php

require_once dirname(__FILE__) . '/../../../config.php';


// This script is simply so I can show sleep progress in bash script
$consoletools = new \ConsoleTools();
if (isset($argv[1]) && is_numeric($argv[1]))
{
	$consoletools->showsleep($argv[1]);
}
