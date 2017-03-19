<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Binaries;

$page = new AdminPage();
$bin  = new Binaries(['Settings' => $page->settings]);

$page->title = "Binary Black/Whitelist List";

$binlist = $bin->getBlacklist(false);
$page->smarty->assign('binlist', $binlist);

$page->content = $page->smarty->fetch('binaryblacklist-list.tpl');
$page->render();
