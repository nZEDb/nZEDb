<?php
require_once("config.php");
require_once(WWW_DIR."lib/miscsorter.php");

$sorter = new MiscSorter(true);

$cat = 7000;
$id = 0;


if (isset($argv[1]))
	$cat = $argv[1];

if (isset($argv[2]))
	$id = $argv[2];

$sorter->nfosorter($cat, $id);

?>