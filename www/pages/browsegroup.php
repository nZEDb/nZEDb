<?php

use nzedb\Groups;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$groups = new Groups(['Settings' => $page->settings]);

$grouplist = $groups->getAll();
$page->smarty->assign('results', $grouplist);

$page->meta_title = "Browse Groups";
$page->meta_keywords = "browse,groups,description,details";
$page->meta_description = "Browse groups";

$page->content = $page->smarty->fetch('browsegroup.tpl');
$page->render();
