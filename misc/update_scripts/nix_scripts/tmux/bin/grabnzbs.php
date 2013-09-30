<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/grabnzbs.php");

$import = new Import(true);

if (isset($argv[1]))
	$import->GrabNZBs($argv[1]);
else
	$import->GrabNZBs($hash='');
