<?php
require_once './config.php';

use nzedb\NZBExport;
use nzedb\Releases;
use nzedb\utility\Utility;

if (Utility::isCLI()) {
	exit ('This script is only for exporting from the web, use the script in misc/testing' . PHP_EOL);
}

$page = new AdminPage();
$rel  = new Releases(['Settings' => $page->settings]);

$folder = $group = $fromDate = $toDate = $gzip = $output = '';
if ($page->isPostBack()) {
	$folder = $_POST["folder"];
	$fromDate = (isset($_POST["postfrom"]) ? $_POST["postfrom"] : '');
	$toDate   = (isset($_POST["postto"]) ? $_POST["postto"] : '');
	$group = $_POST["group"];
	$gzip = $_POST["gzip"];

	if ($folder != '') {
		$output = (new NZBExport(['Browser'  => true, 'Settings' => $page->settings, 'Releases' => $rel]))->beginExport([
				$folder, $fromDate, $toDate, ($_POST["group"] === '-1' ? 0 : (int)$_POST["group"]),
				($_POST["gzip"] === '1' ? true : false)
			]
		);
	} else {
		$output = 'Error, a path is required!';
	}
} else {
	$fromDate = $rel->getEarliestUsenetPostDate();
	$toDate = $rel->getLatestUsenetPostDate();
}

$page->title = "Export Nzbs";
$page->smarty->assign([
		'output'    => $output,
		'folder'    => $folder,
		'fromdate'  => $fromDate,
		'todate'    => $toDate,
		'group'     => $group,
		'gzip'      => $gzip,
		'gziplist'  => [1 => 'True', 0 => 'False'],
		'grouplist' => $rel->getReleasedGroupsForSelect(true)
	]
);
$page->content = $page->smarty->fetch('nzb-export.tpl');
$page->render();
