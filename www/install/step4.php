<?php
require_once '../lib/InstallPage.php';
require_once '../lib/Install.php';

$page = new InstallPage();
$page->title = "Save Settings";

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

$cfg = $cfg->getSession();

$cfg->saveConfigCheck = $cfg->saveConfig();
if ($cfg->saveConfigCheck === false) {
	$cfg->error = true;
}

$cfg->saveLockCheck = $cfg->saveInstallLock();
if ($cfg->saveLockCheck === false) {
	$cfg->error = true;
}

if (!$cfg->error) {
	$cfg->setSession();
}

$page->smarty->assign('cfg', $cfg);
$page->smarty->assign('page', $page);

$page->content = $page->smarty->fetch('step4.tpl');
$page->render();
