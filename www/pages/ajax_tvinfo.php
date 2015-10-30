<?php

use nzedb\Releases;
use nzedb\Videos;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST["id"])) {
	$page->show404();
}

$r = new Releases(['Settings' => $page->settings]);
$rel = $r->getByGuid($_REQUEST["id"]);

if (!$rel) {
	print "No tv info";
} else {
	//print "<h3 class=\"tooltiphead\">episode info...</h3>\n";
	print "<ul>\n";
	if (isset($rel['title'])) {
		print "<li>" . htmlentities($rel["title"], ENT_QUOTES) . "</li>\n";
	}
	print "<li>Aired on " . date("F j, Y", strtotime($rel["firstaired"])) . "</li>\n";
	print "</ul>";

	if ($rel["videos_id"] > 0) {
		$t = new Videos(['Settings' => $page->settings]);
		$show = $t->getByVideoID($rel["videos_id"]);
		if (count($show) > 0) {
			if ($show["image"] == 1) {
				print "<img class=\"shadow\" src=\"/covers/tvshows/" . $show["id"] . ".jpg\" width=\"180\"/>";
			}
		}
	}
}
