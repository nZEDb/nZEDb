<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$rc  = new ReleaseComments($page->settings);
$sab = new SABnzbd($page);

$userid = 0;
if (isset($_GET["id"])) {
	$userid = $_GET["id"] + 0;
} elseif (isset($_GET["name"])) {
	$res = $page->users->getByUsername($_GET["name"]);
	if ($res) {
		$userid = $res["id"];
	}
} else {
	$userid = $page->users->currentUserId();
}

$data = $page->users->getById($userid);
if (!$data) {
	$page->show404();
}

// Get the users API request count for the day.
$apiRequests = $page->users->getApiRequests($userid);
if (!$apiRequests) {
	$apiRequests = 0;
}
$page->smarty->assign('apirequests', $apiRequests['num']);

$invitedby = '';
if ($data["invitedby"] != "") {
	$invitedby = $page->users->getById($data["invitedby"]);
}

// Check if the user selected a theme.
if (!isset($data['style']) || $data['style'] == 'None') {
	$data['style'] = 'Using the admin selected theme.';
}

$page->smarty->assign('userinvitedby', $invitedby);
$page->smarty->assign('user', $data);

$commentcount = $rc->getCommentCountForUser($userid);
$offset       = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $commentcount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', "/profile?id=" . $userid . "&offset=");
$page->smarty->assign('pagerquerysuffix', "#comments");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$commentslist = $rc->getCommentsForUserRange($userid, $offset, ITEMS_PER_PAGE);
$page->smarty->assign('commentslist', $commentslist);

$exccats = $page->users->getCategoryExclusionNames($userid);
$page->smarty->assign('exccats', implode(",", $exccats));

$page->smarty->assign('saburl', $sab->url);
$page->smarty->assign('sabapikey', $sab->apikey);

$sabapikeytypes = [
	SABnzbd::API_TYPE_NZB => 'Nzb Api Key', SABnzbd::API_TYPE_FULL => 'Full Api Key'
];
if ($sab->apikeytype != "") {
	$page->smarty->assign('sabapikeytype', $sabapikeytypes[$sab->apikeytype]);
}

$sabpriorities = [
	SABnzbd::PRIORITY_FORCE  => 'Force', SABnzbd::PRIORITY_HIGH => 'High',
	SABnzbd::PRIORITY_NORMAL => 'Normal', SABnzbd::PRIORITY_LOW => 'Low'
];
if ($sab->priority != "") {
	$page->smarty->assign('sabpriority', $sabpriorities[$sab->priority]);
}

$sabsettings = [1 => 'Site', 2 => 'Cookie'];
$page->smarty->assign('sabsetting', $sabsettings[($sab->checkCookie() === true ? 2 : 1)]);

$page->meta_title       = "View User Profile";
$page->meta_keywords    = "view,profile,user,details";
$page->meta_description = "View User Profile for " . $data["username"];

$page->content = $page->smarty->fetch('profile.tpl');
$page->render();
