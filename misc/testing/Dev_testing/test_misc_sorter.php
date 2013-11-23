<?php
require dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'miscsorter.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'site.php';

$sorter = new MiscSorter(true);

$s = new Sites();
$site = $s->get();
$nntp = new Nntp();
if (($site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
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
