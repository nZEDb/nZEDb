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
		if (isset($predata['nuked'])) {
			$nuked = '';
			switch($predata['nuked']) {
				case IRCScraper::NUKE:
					$nuked = 'NUKED';
					break;
				case IRCScraper::MOD_NUKE:
					$nuked = 'MODNUKED';
					break;
				case IRCScraper::OLD_NUKE:
					$nuked = 'OLDNUKE';
					break;
				case IRCScraper::RE_NUKE:
					$nuked = 'RENUKE';
					break;
				case IRCScraper::UN_NUKE:
					$nuked = 'UNNUKED';
					break;
			}
			if ($nuked !== '') {
				print "<tr><th>" . $nuked . ":</th><td>" . htmlentities((isset($predata['nukereason']) ? $predata['nukereason'] : ''), ENT_QUOTES) . "</td></tr>\n";
			}
		}
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
		if (isset($predata['files'])) {
			print "<tr><th>Files:</th><td>" . htmlentities((strpos($predata['files'], 'B') ? $predata["files"] : ($predata["files"] . 'MB')), ENT_QUOTES) . "</td></tr>\n";
		}
		print "<tr><th>Pred:</th><td>" . htmlentities($predata["predate"], ENT_QUOTES) . "</td></tr>\n";
	print "</table>";
}
