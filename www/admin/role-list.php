<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/users.php");

$page = new AdminPage();

$users = new Users();

$page->title = "User Role List";

//get the user roles
$userroles = $users->getRoles();

$page->smarty->assign('userroles',$userroles);	

$page->content = $page->smarty->fetch('role-list.tpl');
$page->render();

?>
