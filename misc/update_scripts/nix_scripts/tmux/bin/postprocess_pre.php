<?php
require_once realpath(dirname(__FILE__) . '/../../../config.php');
require_once nZEDb_LIB . 'predb.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$s = new Sites();
$site = $s->get();

$nntp = new Nntp();
if (($site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
{
	$c = new ColorCLI;
	echo $c->error("Unable to connect to usenet.\n");
	return;
}

$postprocess = new PostProcess(true);
$postprocess->processPredb($nntp);
if ($site->nntpproxy === false)
	$nntp->doQuit();
