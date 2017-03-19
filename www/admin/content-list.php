<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Contents;

$page        = new AdminPage();
$contents    = new Contents(['Settings' => $page->settings]);
$contentlist = $contents->getAll();
$page->smarty->assign('contentlist', $contentlist);

$page->title = "Content List";

$page->content = $page->smarty->fetch('content-list.tpl');
$page->render();
