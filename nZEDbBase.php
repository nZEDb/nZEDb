<?php

if ($argc > 1) {
	include_once 'nzedb/constants.php';
	$constant = $argv[1];
	if (defined($constant)) {
		exit(constant($constant));
	}

}

exit(dirname(__FILE__));

?>
