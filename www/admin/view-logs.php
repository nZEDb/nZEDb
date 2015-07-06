<?php

require_once './config.php';

use nzedb\Logger;

$page        = new AdminPage();
$page->title = 'View Logs';

$type = isset($_GET['t']) ? $_GET['t'] : 'all';
$page->smarty->assign('type', $type);
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

$logPath = Logger::getDefaultLogPaths();
$logPath = $logPath['LogPath'];

$regex = false;

switch ($type) {
	case 'info':
		$regex = '/\[INFO\]/';
		break;
	case 'notice':
		$regex = '/\[NOTICE\]/';
		break;
	case 'warning':
		$regex = '/\[WARN\]/';
		break;
	case 'error':
		$regex = '/\[ERROR\]/';
		break;
	case 'fatal':
		$regex = '/\[FATAL\]/';
		break;
	case 'sql':
		$regex = '/\[SQL\]/';
		break;
	case 'all':
	default:
		break;
}

$data = $file = false;
if (is_file($logPath)) {
	$file = file($logPath);
}
$total = 0;
if ($file !== false) {
	rsort($file);
	$data = [];
	foreach ($file as $line) {
		$line = str_replace(['>', '<'], '', $line);
		if ($regex !== false) {
			if (preg_match($regex, $line)) {
				$data[] = $line;
			}
		} else {
			$data[] = $line;
		}
	}
	if (count($data) === 0) {
		$data = false;
	} else {
		$total = count($data);
		$data  = array_slice($data, $offset, ITEMS_PER_PAGE);
	}
}

$page->smarty->assign(
	[
		'data'  => $data,
		'types' => ['all', 'info', 'notice', 'warning', 'error', 'fatal', 'sql'],
		'path'  => nZEDb_WWW . 'smarty.php'
	]
);

$page->smarty->assign('pagertotalitems', $total);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/view-logs.php?t=" . $type . "&amp;offset=");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$page->content = $page->smarty->fetch('view-logs.tpl');
$page->render();
