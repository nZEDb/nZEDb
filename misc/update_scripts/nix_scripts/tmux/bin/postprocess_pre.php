<?php
require_once(dirname(__FILE__).'/../../../config.php');
require_once(WWW_DIR.'lib/predb.php');
require_once(WWW_DIR.'lib/site.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/ColorCLI.php');

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
