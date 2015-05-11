<?php

use nzedb\Releases;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST["id"])) {
	$page->show404();
}

$r = new Releases(['Settings' => $page->settings]);
$rel = $r->getByGuid($_REQUEST["id"]);

if (!$rel) {
	print "No release info found";
} else {
	print "<table>\n";
	print "<tr><th>Title:</th><td>" . htmlentities($rel["searchname"], ENT_QUOTES) . "</td></tr>\n";
	if (isset($rel["category_name"]) && $rel["category_name"] != "") {
		print "<tr><th>Cat:</th><td>" . htmlentities($rel["category_name"], ENT_QUOTES) . "</td></tr>\n";
	}
	print "<tr><th>Group:</th><td>" . htmlentities($rel["group_name"], ENT_QUOTES) . "</td></tr>\n";
	if (isset($rel["size"])) {
		if (preg_match('/\d+/', $rel["size"], $size)) {
			;
		}
		if ($size[0] > 0) {
			print "<tr><th>Size:</th><td>" . htmlentities(floor($rel["size"] / 1024 / 1024), ENT_QUOTES) . " MB</td></tr>\n";
		}
	}
	print "<tr><th>Posted:</th><td>" . htmlentities($rel["postdate"], ENT_QUOTES) . "</td></tr>\n";
	print "</table>";
}
