<?php

use app\models\Settings;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['action']) && $_GET['action'] == "1" && isset($_GET['emailto'])) {
	$emailto = $_GET['emailto'];
	$ret = $page->users->sendInvite(Settings::value('title'), Settings::value('email'), $page->serverurl, $page->users->currentUserId(), $emailto);
	if (!$ret) {
		print "Invite not sent.";
	} else {
		print "Invite sent. Alternatively paste them following link to register - " . $ret;
	}
} else {
	print "Invite not sent.";
}
