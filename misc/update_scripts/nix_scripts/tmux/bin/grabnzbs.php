<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'grabnzbs.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'site.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from grabnzbs_threaded.py."));

$s = new Sites();
$site = $s->get();

$nntp = new Nntp();
if (($site->grabnzbs == '2' ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
	exit($c->error("Unable to connect to usenet."));
if ($site->nntpproxy === "1")
	usleep(500000);

$import = new Import(true);

if (isset($argv[1]))
	$import->GrabNZBs($argv[1], $nntp);
else
	$import->GrabNZBs($hash='', $nntp);
if ($site->nntpproxy != "1")
	$nntp->doQuit();
?>
