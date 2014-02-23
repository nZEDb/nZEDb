<?php
require_once './config.php';


$page = new AdminPage();
$contents = new Contents();
$contentlist = $contents->getAll();
$page->smarty->assign('contentlist',$contentlist);

$page->title = "Content List";

$page->content = $page->smarty->fetch('content-list.tpl');
$page->render();

?>
