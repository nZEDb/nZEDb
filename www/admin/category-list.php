<?php
require_once './config.php';

$page     = new AdminPage();
$category = new Category(['Settings' => $page->settings]);

$page->title = "Category List";

$categorylist = $category->getFlat();
$page->smarty->assign('categorylist', $categorylist);

$page->content = $page->smarty->fetch('category-list.tpl');
$page->render();
