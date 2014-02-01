<?php
require_once realpath(dirname(__FILE__) . '/../config.php');
require_once '../lib/InstallPage.php';
require_once '../lib/Install.php';

$page = new InstallPage();

if (!isset($_REQUEST["success"])) {
	$page->title = "NZB File Path";
}

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

$cfg = $cfg->getSession();

if ($page->isPostBack()) {
	$cfg->doCheck = true;

	$cfg->NZB_PATH = trim($_POST['nzbpath']);

	if ($cfg->NZB_PATH == '') {
		$cfg->error = true;
	} else {
		$cfg->nzbPathCheck = is_writable($cfg->NZB_PATH);
		if ($cfg->nzbPathCheck === false) {
			$cfg->error = true;
		}

		$lastchar = substr($cfg->NZB_PATH, strlen($cfg->NZB_PATH) - 1);
		if ($lastchar != "/") {
			$cfg->NZB_PATH = $cfg->NZB_PATH . "/";
		}

		if (!$cfg->error) {
			if (!file_exists($cfg->NZB_PATH . "tmpunrar")) {
				mkdir($cfg->NZB_PATH . "tmpunrar");
			}

			require_once $cfg->nZEDb_WWW . '/lib/DB.php';
			$db = new DB();
			$sql1 = sprintf("UPDATE site SET value = %s WHERE setting = 'nzbpath'", $db->escapeString($cfg->NZB_PATH));
			$sql2 = sprintf("UPDATE site SET value = %s WHERE setting = 'tmpunrarpath'", $db->escapeString($cfg->NZB_PATH . "tmpunrar"));
			if ($db->queryExec($sql1) === false || $db->queryExec($sql2) === false) {
				$cfg->error = true;
			}
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

$page->content = $page->smarty->fetch('step6.tpl');
$page->render();
