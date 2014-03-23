<?php
require_once './config.php';


$page = new AdminPage();
$users = new Users();

$page->title = "User Role List";

// Get the user roles.
$userroles = $users->getRoles();

$page->smarty->assign('userroles',$userroles);

$page->content = $page->smarty->fetch('role-list.tpl');
$page->render();

?>
