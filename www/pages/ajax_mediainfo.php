<?php

use nzedb\ReleaseExtra;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_REQUEST['id'])) {
	$page->show404();
}

$re = new ReleaseExtra($page->settings);
$redata = $re->getBriefByGuid($_REQUEST['id']);

if (!$redata) {
	echo 'No media info';
} else {
	echo "<table>\n";
	if ($redata['videocodec'] != '' && $redata['containerformat'] != '') {
		$redata['videocodec'] = $re->makeCodecPretty($redata['videocodec']);
		echo '<tr><th>Format:</th><td>' . htmlentities($redata['videocodec'], ENT_QUOTES) . ' - ' . htmlentities($redata['containerformat'], ENT_QUOTES) . "</td></tr>\n";
	}
	if ($redata['videoduration'] != '') {
		echo '<tr><th>Duration:</th><td>' . htmlentities($redata['videoduration'], ENT_QUOTES) . "</td></tr>\n";
	}
	if ($redata['size'] != '') {
		echo '<tr><th>Resolution:</th><td>' . htmlentities($redata['size'], ENT_QUOTES) . "</td></tr>\n";
	}
	if ($redata['videoaspect'] != '') {
		echo '<tr><th>Aspect Ratio:</th><td>' . htmlentities($redata['videoaspect'], ENT_QUOTES) . "</td></tr>\n";
	}
	if ($redata['audio'] != '' && $redata['audio'] != ', ') {
		echo '<tr><th>Audio Languages:</th><td>' . htmlentities($redata['audio'], ENT_QUOTES) . "</td></tr>\n";
	}
	if ($redata['audioformat'] != '' && $redata['audioformat'] != ', ') {
		echo '<tr><th>Audio Format:</th><td>' . htmlentities($redata['audioformat'], ENT_QUOTES) . "</td></tr>\n";
	}
	if ($redata['subs'] != '') {
		echo '<tr><th>Subtitles:</th><td>' . htmlentities($redata['subs'], ENT_QUOTES) . "</td></tr>\n";
	}
	echo '</table>';
}
