<?php
require dirname(__FILE__) . '/../../../www/config.php';

$pdo = new \nzedb\db\Settings();
$sorter = new \MiscSorter(true, $pdo);

$cat = \Category::CAT_MISC;
$id = 0;

if (isset($argv[1])) {
	$cat = $argv[1];
}

if (isset($argv[2])) {
	$id = $argv[2];
}

$sorter->nfosorter($cat, $id);
