<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

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
$count = 0;
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
		$count = count($data);
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

$page->smarty->assign('pageroffset', $offset);


$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/view-logs.php?t=" . $type . "&amp;offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('view-logs.tpl');
$page->render();
// TODO modelise.
