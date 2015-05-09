<?php
require_once './config.php';

use nzedb\Logging;
use nzedb\Releases;

$page     = new AdminPage();
$releases = new Releases(['Settings' => $page->settings]);
$logging  = new Logging(['Settings' => $page->settings]);
if ($page->settings->getSetting('loggingopt') == '0') {
	$loggingon = '0';
} else {
	$loggingon = '1';
}

$page->smarty->assign('loggingon', $loggingon);

$page->title = 'Site Stats';

$topgrabs = $page->users->getTopGrabbers();
$page->smarty->assign('topgrabs', $topgrabs);

$topdownloads = $releases->getTopDownloads();
$page->smarty->assign('topdownloads', $topdownloads);

$topcomments = $releases->getTopComments();
$page->smarty->assign('topcomments', $topcomments);

$recent = $releases->getRecentlyAdded();
$page->smarty->assign('recent', $recent);

if ($loggingon == '1') {
	$toplogincombined = $logging->getTopCombined();
	$page->smarty->assign('toplogincombined', $toplogincombined);

	$toploginips = $logging->getTopIPs();
	$page->smarty->assign('toploginips', $toploginips);
}

$page->content = $page->smarty->fetch('site-stats.tpl');
$page->render();
