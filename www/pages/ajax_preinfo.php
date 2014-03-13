<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST["id"])) {
	$page->show404();
}

$pre = new PreDb();
$predata = $pre->getOne($_REQUEST["id"]);

if (!$predata) {
	print "No pre info";
} else {
	print "<table>\n";
	print "<tr><th>Title:</th><td>" . htmlentities($predata["title"], ENT_QUOTES) . "</td></tr>\n";
	if (isset($predata["category"]) && $predata["category"] != "") {
		print "<tr><th>Cat:</th><td>" . htmlentities($predata["category"], ENT_QUOTES) . "</td></tr>\n";
	}
	print "<tr><th>Source:</th><td>" . htmlentities($predata["source"], ENT_QUOTES) . "</td></tr>\n";
	if (isset($predata["size"])) {
		if (preg_match('/\d+/', $predata["size"], $size)) {
			;
		}
		if (isset($size[0]) && $size[0] > 0) {
			print "<tr><th>Size:</th><td>" . htmlentities($predata["size"], ENT_QUOTES) . "</td></tr>\n";
		}
	}
	print "<tr><th>Pred:</th><td>" . htmlentities($predata["predate"], ENT_QUOTES) . "</td></tr>\n";
	print "</table>";
}
