<?php
require_once realpath(__DIR__ . '/../automated.config.php');

$page = new InstallPage();
$page->title = "News Server Setup";

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

$cfg = $cfg->getSession();

if ($page->isPostBack()) {
	$cfg->doCheck = true;

	$cfg->NNTP_SERVER = trim($_POST['server']);
	$cfg->NNTP_USERNAME = trim($_POST['user']);
	$cfg->NNTP_PASSWORD = trim($_POST['pass']);
	$cfg->NNTP_PORT = (trim($_POST['port']) == '') ? 119 : trim($_POST['port']);
	$cfg->NNTP_SSLENABLED = (isset($_POST['ssl']) ? (trim($_POST['ssl']) == '1' ? true : false) : false);
	$cfg->NNTP_SOCKET_TIMEOUT = (is_numeric(trim($_POST['socket_timeout'])) ? (int) trim($_POST['socket_timeout']) : 120);

	require_once nZEDb_LIBS . 'Net_NNTP/NNTP/Client.php';
	$test = new Net_NNTP_Client();
	$pear_obj = new PEAR();

	$enc = false;
	if ($cfg->NNTP_SSLENABLED) {
		$enc = "tls";
	}

	// test connection
	$cfg->nntpCheck = $test->connect($cfg->NNTP_SERVER, $enc, $cfg->NNTP_PORT);
	if ($pear_obj->isError($cfg->nntpCheck)) {
		$cfg->nntpCheck->message = 'Connection error, check your server name, port and SSL: (' . $cfg->nntpCheck->getMessage() . ')';
		$cfg->error = true;
    }
	//test authentication if username and password are provided
	else if ($cfg->NNTP_USERNAME != '' && $cfg->NNTP_PASSWORD != '') {
		$cfg->nntpCheck = $test->authenticate($cfg->NNTP_USERNAME, $cfg->NNTP_PASSWORD);
		if ($pear_obj->isError($cfg->nntpCheck)) {
			$cfg->nntpCheck->message = 'Authentication error, check your username and password: (' . $cfg->nntpCheck->getMessage() .')';
			$cfg->error = true;
		}
	}

	if (!$cfg->error) {
		$cfg->setSession();
		header("Location: ?success");
		die();
	}
}

$page->smarty->assign('cfg', $cfg);
$page->smarty->assign('page', $page);

$page->content = $page->smarty->fetch('step4.tpl');
$page->render();
