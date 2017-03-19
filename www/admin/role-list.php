<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

$page = new AdminPage();

$page->title = "User Role List";

// Get the user roles.
$page->smarty->assign('userroles', $page->users->getRoles());

$page->content = $page->smarty->fetch('role-list.tpl');
$page->render();
