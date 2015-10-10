<?php
require_once './config.php';

use nzedb\Menu;

$page = new AdminPage();
$menu = new Menu($page->settings);

$page->title = "Menu List";

$menulist = $menu->getAll();
$page->smarty->assign('menulist', $menulist);

$page->content = $page->smarty->fetch('menu-list.tpl');
$page->render();
