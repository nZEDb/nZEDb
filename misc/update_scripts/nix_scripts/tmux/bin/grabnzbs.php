<?php
require_once(dirname(__FILE__).'/../../../config.php');
require_once(WWW_DIR.'lib/grabnzbs.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/ColorCLI.php');

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from update_threaded.py.\n"));

$s = new Sites();
$site = $s->get();

$nntp = new Nntp();

if (($site->grabnzbs == '2' ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
{
	echo $c->error("Unable to connect to usenet.\n");
	return;
}

$import = new Import(true);

if (isset($argv[1]))
	$import->GrabNZBs($argv[1], $nntp);
else
	$import->GrabNZBs($hash='', $nntp);
$nntp->doQuit();
