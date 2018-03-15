<?php

use nzedb\PreDb;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST['id'])) {
	$page->show404();
}

$pre = new PreDb(['Settings' => $page->settings]);
$predata = $pre->getOne($_REQUEST['id']);

if (!$predata) {
	echo 'No pre info';
} else {
	echo "<table>\n";
	if (isset($predata['nuked'])) {
		$nuked = '';
		switch ($predata['nuked']) {
				case PreDb::PRE_NUKED:
					$nuked = 'NUKED';
					break;
				case PreDb::PRE_MODNUKE:
					$nuked = 'MODNUKED';
					break;
				case PreDb::PRE_OLDNUKE:
					$nuked = 'OLDNUKE';
					break;
				case PreDb::PRE_RENUKED:
					$nuked = 'RENUKE';
					break;
				case PreDb::PRE_UNNUKED:
					$nuked = 'UNNUKED';
					break;
			}
		if ($nuked !== '') {
			echo '<tr><th>' . $nuked . ':</th><td>' . htmlentities(($predata['nukereason'] ?? ''), ENT_QUOTES) . "</td></tr>\n";
		}
	}
	echo '<tr><th>Title:</th><td>' . htmlentities($predata['title'], ENT_QUOTES) . "</td></tr>\n";
	if (isset($predata['category']) && $predata['category'] != '') {
		echo '<tr><th>Cat:</th><td>' . htmlentities($predata['category'], ENT_QUOTES) . "</td></tr>\n";
	}
	echo '<tr><th>Source:</th><td>' . htmlentities($predata['source'], ENT_QUOTES) . "</td></tr>\n";
	if (isset($predata['size'])) {
		if (preg_match('/\d+/', $predata['size'], $size)) {
			;
		}
		if (isset($size[0]) && $size[0] > 0) {
			echo '<tr><th>Size:</th><td>' . htmlentities($predata['size'], ENT_QUOTES) . "</td></tr>\n";
		}
	}
	if (isset($predata['files'])) {
		echo '<tr><th>Files:</th><td>' . htmlentities((preg_match('/F|B/', $predata['files'], $match) ? $predata['files'] : ($predata['files'] . 'MB')), ENT_QUOTES) . "</td></tr>\n";
	}
	echo '<tr><th>Pred:</th><td>' . htmlentities($predata['created'], ENT_QUOTES) . "</td></tr>\n";
	echo '</table>';
}
