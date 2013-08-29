<?php
require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/category.php");

$page = new AdminPage();
$category = new Category();

$page->title = "Category List";

$categorylist = $category->getFlat();
$page->smarty->assign('categorylist',$categorylist);

$page->content = $page->smarty->fetch('category-list.tpl');
$page->render();

?>
