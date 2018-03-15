<?php

use app\models\Settings;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['action']) && $_GET['action'] == '1' && isset($_GET['emailto'])) {
	$emailto = $_GET['emailto'];
	$ret = $page->users->sendInvite(
		Settings::value('site.main.title'),
		Settings::value('site.main.email'),
		$page->serverurl,
		$page->users->currentUserId(),
		$emailto
	);
	if (!$ret) {
		echo 'Invite not sent.';
	} else {
		echo 'Invite sent. Alternatively paste them following link to register - ' . $ret;
	}
} else {
	echo 'Invite not sent.';
}
