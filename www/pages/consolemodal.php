<?php

use nzedb\Console;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET["id"]) && ctype_digit($_GET["id"])) {
	$console = new Console(['Settings' => $page->settings]);
	$con = $console->getConsoleInfo($_GET['id']);
	if (!$con) {
		$page->show404();
	}

	$page->smarty->assign('console', $con);

	$page->title = "Info for " . $con['title'];
	$page->meta_title = "";
	$page->meta_keywords = "";
	$page->meta_description = "";
	$page->smarty->registerPlugin('modifier', 'ss', 'stripslashes');

	$modal = false;
	if (isset($_GET['modal'])) {
		$modal = true;
		$page->smarty->assign('modal', true);
	}

	$page->content = $page->smarty->fetch('viewconsole.tpl');

	if ($modal) {
		echo $page->content;
	} else {
		$page->render();
	}
}
