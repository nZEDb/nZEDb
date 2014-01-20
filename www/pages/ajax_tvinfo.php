<?php

if (!$users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST["id"])) {
	$page->show404();
}

//require_once nZEDb_LIB . 'releases.php';
$r = new Releases();
$rel = $r->getByGuid($_REQUEST["id"]);

if (!$rel) {
	print "No tv info";
} else {
	//print "<h3 class=\"tooltiphead\">episode info...</h3>\n";
	print "<ul>\n";
	print "<li>" . htmlentities($rel["tvtitle"], ENT_QUOTES) . "</li>\n";
	print "<li>Aired on " . date("F j, Y", strtotime($rel["tvairdate"])) . "</li>\n";
	print "</ul>";


	if ($rel["rageid"] > 0) {
		require_once nZEDb_LIB . 'tvrage.php';
		$t = new TvRage();
		$rage = $t->getByRageID($rel["rageid"]);
		if (count($rage) > 0) {
			if ($rage[0]["imgdata"] != "") {
				print "<img class=\"shadow\" src=\"" . WWW_TOP . "/getimage?type=tvrage&amp;id=" . $rage[0]["id"] . "\" width=\"180\"/>";
			}
		}
	}
}
