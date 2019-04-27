<?php

if (!defined('nZEDb_ROOT')) {
	define('nZEDb_ROOT', realpath(__DIR__) . DIRECTORY_SEPARATOR);
}

if (isset($argc) && $argc > 1) {
	$constant = $argv[1];
	require_once 'nzedb/constants.php';

	if (defined($constant)) {
		exit(constant($constant));
	}
}

//exit(dirname(__FILE__));

?>
