<?php
require_once realpath(dirname(__FILE__) . '/../../../config.php');
require_once nZEDb_LIB . 'consoletools.php';

$consoletools = new consoleTools();
if (isset($argv[1]))
	$consoletools->showsleep($argv[1]);
