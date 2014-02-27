<?php
require_once realpath(__DIR__ . '/../automated.config.php');

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
	$cfg->COVERS_PATH = trim($_POST['coverspath']);

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
	}

	if ($cfg->COVERS_PATH == '') {
		$cfg->error = true;
	} else {
		Util::trailingSlash($cfg->COVERS_PATH);

		$cfg->coverPathCheck = is_writable($cfg->COVERS_PATH);
		if ($cfg->coverPathCheck === false) {
			$cfg->error = true;
		}
	}

	if (!$cfg->error) {
		if (!file_exists($cfg->UNRAR_PATH)) {
			mkdir($cfg->UNRAR_PATH);
		}

		$db = new DB();
		$sql1 = sprintf("UPDATE site SET value = %s WHERE setting = 'nzbpath'", $db->escapeString($cfg->NZB_PATH));
		$sql2 = sprintf("UPDATE site SET value = %s WHERE setting = 'tmpunrarpath'", $db->escapeString($cfg->UNRAR_PATH));
		$sql3 = sprintf("UPDATE site SET value = %s WHERE setting = 'coverspath'", $db->escapeString($cfg->COVERS_PATH));
		if ($db->queryExec($sql1) === false || $db->queryExec($sql2) === false || $db->queryExec($sql3) === false) {
			$cfg->error = true;
		}

		if ($cfg->error !== true) {
			if ($cfg->COVERS_PATH != nZEDb_WWW . 'covers' . DS) {
				// TODO clean up old covers location if 'empty' (i.e. only contains the versioned files).
			}

			if ($cfg->NZB_PATH != nZEDb_ROOT . 'nzbfiles' . DS) {
				// TODO clean up old nzbfiles location if 'empty' (i.e. only contains the versioned files).
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
