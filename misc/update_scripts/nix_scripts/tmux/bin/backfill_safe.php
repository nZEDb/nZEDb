<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/binaries.php");

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);

	if (isset($pieces[3]))
	{
		$binaries = new Binaries();
		$binaries->getRange($pieces[0], $pieces[1], $pieces[2], $pieces[3]);
	}
}
?>
