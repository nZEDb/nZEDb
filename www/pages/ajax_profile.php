<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['action']) && $_GET['action'] == "1" && isset($_GET['emailto'])) {
	$emailto = $_GET['emailto'];
	$ret = $users->sendInvite($page->settings->getSetting('title'), $page->settings->getSetting('email'), $page->serverurl, $users->currentUserId(), $emailto);
	if (!$ret) {
		print "Invite not sent.";
	} else {
		print "Invite sent. Alternatively paste them following link to register - " . $ret;
	}
} else {
	print "Invite not sent.";
}
