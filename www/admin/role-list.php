<?php
require_once './config.php';

$page = new AdminPage();

$page->title = "User Role List";

// Get the user roles.
$page->smarty->assign('userroles',(new Users())->getRoles());

$page->content = $page->smarty->fetch('role-list.tpl');
$page->render();