<?php
require_once realpath(__DIR__ . '/../automated.config.php');

use nzedb\db\Settings;

$page = new InstallPage();

if (!isset($_REQUEST["success"])) {
	$page->title = "File Paths";
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
	$cfg->UNRAR_PATH = trim($_POST['tmpunrarpath']);

	if (extension_loaded('posix') && strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
		$group = posix_getgrgid(posix_getgid());
		$fixString = '<br /><br />Another solution is to run:<br />chown -R YourUnixUserName:' . $group['name']  . ' ' . nZEDb_ROOT .
		'<br />Then give your user access to the group:<br />usermod -a -G ' . $group['name'] . ' YourUnixUserName' .
		'<br />Finally give read/write access to your user/group:<br />chmod -R 774 ' . nZEDb_ROOT;
		$page->smarty->assign('fixString', $fixString);
	}

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

	if ($cfg->UNRAR_PATH == '') {
		$cfg->error = true;
	} else {
		$cfg->unrarPathCheck = is_writable($cfg->NZB_PATH);
		if ($cfg->unrarPathCheck === false) {
			$cfg->error = true;
		}

		$lastchar = substr($cfg->UNRAR_PATH, strlen($cfg->UNRAR_PATH) - 1);
		if ($lastchar != "/") {
			$cfg->UNRAR_PATH = $cfg->UNRAR_PATH . "/";
		}
	}
	/*
		if ($cfg->COVERS_PATH == '') {
			$cfg->error = true;
		} else {
			\nzedb\utility\Utility::trailingSlash($cfg->COVERS_PATH);

			$cfg->coverPathCheck = is_writable($cfg->COVERS_PATH);
			if ($cfg->coverPathCheck === false) {
				$cfg->error = true;
			}
		}
	 */

	if (!$cfg->error) {
		if (!file_exists($cfg->UNRAR_PATH)) {
			mkdir($cfg->UNRAR_PATH);
		}

		$pdo = new Settings();
		$sql1 = sprintf("UPDATE settings SET value = %s WHERE setting = 'nzbpath'", $pdo->escapeString($cfg->NZB_PATH));
		$sql2 = sprintf("UPDATE settings SET value = %s WHERE setting = 'tmpunrarpath'", $pdo->escapeString($cfg->UNRAR_PATH));
		$sql3 = sprintf("UPDATE settings SET value = %s WHERE setting = 'coverspath'", $pdo->escapeString($cfg->COVERS_PATH));
		if ($pdo->queryExec($sql1) === false || $pdo->queryExec($sql2) === false || $pdo->queryExec($sql3) === false) {
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

$page->content = $page->smarty->fetch('step7.tpl');
$page->render();
