<?php

use nzedb\ReleaseFiles;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST["id"])) {
	$page->show404();
}

$rf = new ReleaseFiles($page->settings);
$files = $rf->getByGuid($_REQUEST["id"]);

if (count($files) == 0) {
	print "No files";
} else {
	//print "<h3 class=\"tooltiphead\">rar archive contains...</h3>\n";
	print "<ul>\n";
	foreach ($files as $f) {
		print "<li>" . htmlentities($f["name"], ENT_QUOTES) . "&nbsp;" . ($f["passworded"] == 1 ? "<img width=\"12\" src=\"" . nZEDb_THEMES_SHARED . "images/icons/lock.gif\" />" : "") . "</li>\n";
	}
	print "</ul>";
}
