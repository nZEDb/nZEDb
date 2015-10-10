<?php
require_once './config.php';

use nzedb\Category;

$page     = new AdminPage();
$page->title = "Category List";
$page->smarty->assign('categorylist', (new Category(['Settings' => $page->settings]))->getFlat());

$page->content = $page->smarty->fetch('category-list.tpl');
$page->render();
