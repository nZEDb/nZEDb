<?php

$releases = new Releases(['Settings' => $page->settings]);
$users = new Users();
$page = new Page();

	if (isset($_GET['userid']) && is_numeric($_GET['userid']) && isset($_GET['rsstoken']) && isset($_GET['guid'])) {
		$rel = $releases->getByGuid($_GET["guid"]);
		$userid = $_GET['userid'];
		$rsstoken = $_GET['rsstoken'];

		if (!$rel)
			$page->show404();

		$alt = $releases->getAlternate($rel['guid'], $rel['searchname'], $userid, true);
		if (!$alt) {
			$page->show404();
		}
		//http://blah.net/getnzb/GUID.nzb&i=<usernumber>&r=APIKEY
		$url = $page->serverurl . 'getnzb/' . $alt['guid'] . '.nzb' . '&i=' . $userid . '&r=' . $rsstoken;
		header('Location: ' . $url . '');
	}