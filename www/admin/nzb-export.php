<?php
require_once './config.php';
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

if (\nzedb\utility\Utility::isCLI()) {
	exit ('This script is only for exporting from the web, use the script in misc/testing' .
		  PHP_EOL);
}

$page = new AdminPage();
$rel  = new Releases(['Settings' => $page->settings]);

if ($page->isPostBack()) {
	$retVal = $path = '';

	$path     = $_POST["folder"];
	$postFrom = (isset($_POST["postfrom"]) ? $_POST["postfrom"] : '');
	$postTo   = (isset($_POST["postto"]) ? $_POST["postto"] : '');
	$group    = ($_POST["group"] === '-1' ? 0 : (int)$_POST["group"]);
	$gzip     = ($_POST["gzip"] === '1' ? true : false);

	if ($path !== "") {
		$NE = new NZBExport([
								'Browser'  => true, 'Settings' => $page->settings,
								'Releases' => $rel
							]);
		$retVal = $NE->beginExport(
			[
				$path,
				$postFrom,
				$postTo,
				$group,
				$gzip
			]
		);
	} else {
		$retVal = 'Error, a path is required!';
	}

	$page->smarty->assign(
		[
			'folder'   => $path,
			'output'   => $retVal,
			'fromdate' => $postFrom,
			'todate'   => $postTo,
			'group'    => $_POST["group"],
			'gzip'     => $_POST["gzip"]
		]
	);
} else {
	$page->smarty->assign(
		[
			'fromdate' => $rel->getEarliestUsenetPostDate(),
			'todate'   => $rel->getLatestUsenetPostDate()
		]
	);
}

$page->title = "Export Nzbs";
$page->smarty->assign(
	[
		'gziplist'  => [1 => 'True', 0 => 'False'],
		'grouplist' => $rel->getReleasedGroupsForSelect(true)
	]
);
$page->content = $page->smarty->fetch('nzb-export.tpl');
$page->render();
