<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'postprocess.php';
require_once nZEDb_LIB . 'tmux.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'site.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from postprocess_threaded.py."));

$s = new Sites();
$site = $s->get();

$tmux = new Tmux;
$torun = $tmux->get()->post;

$pieces = explode('           =+=            ', $argv[1]);

$postprocess = new PostProcess(true);
if (isset($pieces[6]))
{
	// Create the connection here and pass, this is for post processing, so check for alternate
	$nntp = new Nntp();
	if (($site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
		exit($c->error("Unable to connect to usenet."));
	if ($site->nntpproxy === "1")
		usleep(500000);

	$postprocess->processAdditionalThreaded($argv[1], $nntp);
	if ($site->nntpproxy != "1")
		$nntp->doQuit();
}
elseif (isset($pieces[3]))
{
	// Create the connection here and pass, this is for post processing, so check for alternate
	$nntp = new Nntp();
	if (($site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
		exit($c->error("Unable to connect to usenet."));
	if ($site->nntpproxy === "1")
		usleep(500000);

	$postprocess->processNfos($argv[1], $nntp);
	if ($site->nntpproxy != "1")
		$nntp->doQuit();
}
elseif (isset($pieces[2]))
{
	$postprocess->processMovies($argv[1]);
	echo '.';
}
elseif (isset($pieces[1]))
	$postprocess->processTv($argv[1]);
?>
