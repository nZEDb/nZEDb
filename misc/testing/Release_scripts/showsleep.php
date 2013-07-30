<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/consoletools.php");

$consoletools = new consoleTools();
if (isset($argv[1]))
	$consoletools->showsleep($argv[1]);
