<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/menu.php");

$page = new AdminPage();

$menu = new Menu();

$page->title = "Menu List";

$menulist = $menu->getAll();
$page->smarty->assign('menulist',$menulist);	

$page->content = $page->smarty->fetch('menu-list.tpl');
$page->render();

?>
