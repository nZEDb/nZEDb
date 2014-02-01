<?php
require_once './config.php';
//require_once nZEDb_LIB . 'adminpage.php';


$page = new AdminPage();
$bin = new Binaries();

$page->title = "Binary Black/Whitelist List";

$binlist = $bin->getBlacklist(false);
$page->smarty->assign('binlist', $binlist);

$page->content = $page->smarty->fetch('binaryblacklist-list.tpl');
$page->render();
