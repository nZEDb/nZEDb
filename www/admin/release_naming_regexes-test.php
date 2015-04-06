<?php
require_once './config.php';

$page = new AdminPage();

$page->title = "Release Naming Regex Test";

$group      = trim(isset($_POST['group']) && !empty($_POST['group']) ? $_POST['group'] : '');
$regex      = trim(isset($_POST['regex']) && !empty($_POST['regex']) ? $_POST['regex'] : '');
$showLimit  = (isset($_POST['showlimit']) && is_numeric($_POST['showlimit']) ? $_POST['showlimit'] : 250);
$queryLimit = (isset($_POST['querylimit']) && is_numeric($_POST['querylimit']) ? $_POST['querylimit'] : 100000);
$page->smarty->assign([
						  'group'      => $group, 'regex' => $regex, 'showlimit' => $showLimit,
						  'querylimit' => $queryLimit
					  ]);

if ($group && $regex) {
	$page->smarty->assign('data',
						  (new Regexes([
										   'Settings'   => $page->settings,
										   'Table_Name' => 'release_naming_regexes'
									   ]))->testReleaseNamingRegex($group,
																   $regex,
																   $showLimit,
																   $queryLimit));
}

$page->content = $page->smarty->fetch('release_naming_regexes-test.tpl');
$page->render();
