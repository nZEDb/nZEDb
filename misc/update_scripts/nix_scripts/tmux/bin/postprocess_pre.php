<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'predb.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$s = new Sites();
$site = $s->get();
$c = new ColorCLI;

// Create the connection here and pass, this is for post processing, so check for alternate
$nntp = new Nntp();
if (($site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
	exit($c->error("Unable to connect to usenet."));
if ($site->nntpproxy === "1")
	usleep(500000);

$predb = new Predb($echooutput=true);
$predb->updatePre();
$predb->checkPre($nntp);
if ($site->nntpproxy != "1")
	$nntp->doQuit();
?>
