<?php

use nzedb\Releases;

if (isset($_GET['guid']) && isset($_GET['searchname']) && isset($_GET['userid']) && is_numeric($_GET['userid']) && isset($_GET['rsstoken'])) {

	$alt = (new Releases(['Settings' => $page->settings]))->getAlternate($_GET['guid'], $_GET['searchname'], $_GET['userid']);
	if (!$alt) {
		$page->show404();
	}
	header('Location: ' . $page->serverurl . 'getnzb/' . $alt['guid'] . '&i=' . $_GET['userid'] . '&r=' . $_GET['rsstoken']);
}
