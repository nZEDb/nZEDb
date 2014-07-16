<?php
require dirname(__FILE__) . '/../../../www/config.php';

$sorter = new MiscSorter(true);

$altNNTP = (new nzedb\db\Settings())->getSetting('alternate_nntp');
$nntp = new NNTP();
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
