<?php
require_once './config.php';
//require_once nZEDb_LIB . 'adminpage.php';
//require_once nZEDb_LIB . 'users.php';

$page = new AdminPage();
$users = new Users();

$page->title = "User Role List";

// Get the user roles.
$userroles = $users->getRoles();

$page->smarty->assign('userroles',$userroles);

$page->content = $page->smarty->fetch('role-list.tpl');
$page->render();

?>
