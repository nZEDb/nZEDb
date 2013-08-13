<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/users.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/logging.php");

$page = new AdminPage();
$users = new Users();
$releases = new Releases();
$logging = new Logging();

$page->title = "Site Stats";

$topgrabs = $users->getTopGrabbers();
$page->smarty->assign('topgrabs', $topgrabs);

$topdownloads = $releases->getTopDownloads();
$page->smarty->assign('topdownloads', $topdownloads);

$topcomments = $releases->getTopComments();
$page->smarty->assign('topcomments', $topcomments);

$recent = $releases->getRecentlyAdded();
$page->smarty->assign('recent', $recent);

$toplogincombined = $logging->getTopCombined();
$page->smarty->assign('toplogincombined', $toplogincombined);

$toploginips = $logging->getTopIPs();
$page->smarty->assign('toploginips', $toploginips);

$page->content = $page->smarty->fetch('site-stats.tpl');
$page->render();

?>
