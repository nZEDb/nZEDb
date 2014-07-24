<?php
require dirname(__FILE__) . '/../../../www/config.php';

$sorter = new MiscSorter(true);

$pdo = new nzedb\db\Settings();
$altNNTP = $pdo->getSetting('alternate_nntp');
$c = new ColorCLI();
$nntp = new NNTP(['Settings' => $pdo, 'ColorCLI' => $c]);
if (($altNNTP === '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true)
{
	echo $c->error("Unable to connect to usenet.\n");
	return;
}

$cat = 7010;
$id = 0;

if (isset($argv[1]))
	$cat = $argv[1];

if (isset($argv[2]))
	$id = $argv[2];

$sorter->nfosorter($cat, $id, $nntp);

$sorter->musicnzb( $cat, $id);
