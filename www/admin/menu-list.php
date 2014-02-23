<?php
require_once './config.php';


$page = new AdminPage();
$menu = new Menu();

$page->title = "Menu List";

$menulist = $menu->getAll();
$page->smarty->assign('menulist',$menulist);

$page->content = $page->smarty->fetch('menu-list.tpl');
$page->render();

?>
