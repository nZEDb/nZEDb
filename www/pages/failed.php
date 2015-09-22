<?php

use nzedb\Releases;
use nzedb\Users;

if (isset($_GET['guid']) && isset($_GET['searchname']) && isset($_GET['userid']) && is_numeric($_GET['userid']) && isset($_GET['rsstoken'])) {

	$page = new Page();
	$releases = new Releases(['Settings' => $page->settings]);
	$users = new Users();

	$userid = $_GET['userid'];
	$rsstoken = $_GET['rsstoken'];
	$rel = $releases->getByGuid($_GET['guid']);

	if (!$rel) {
		$page->show404();
	}

	$alt = $releases->getAlternate($_GET['guid'], $_GET['searchname'], $userid);
	if (!$alt) {
		$page->show404();
	}

	$url = $page->serverurl . 'getnzb/' . $alt['guid'] . '.nzb' . '&i=' . $userid . '&r=' . $rsstoken;
	header('Location: ' . $url . '');
}
