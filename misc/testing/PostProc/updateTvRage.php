<?php

//This script will update all records in the tvrage table

require_once dirname(__FILE__) . '/../../../www/config.php';

$tvrage = new TvRage(true);
$db = new Db();
$c = new ColorCLI();

$shows = $db->queryDirect("SELECT rageid FROM tvrage WHERE imgdata IS NULL ORDER BY rageid DESC");
if ($shows->rowCount() > 0) {
	echo $c->header("Updating " . number_format($shows->rowCount()) . " tv shows.");
}

$loop = 0;
foreach ($shows as $show) {
	$starttime = microtime(true);
	$rageid = $show['rageid'];
	$tvrShow = $tvrage->getRageInfoFromService($rageid);
	$genre = '';
	if (isset($tvrShow['genres']) && is_array($tvrShow['genres']) && !empty($tvrShow['genres'])) {
		if (is_array($tvrShow['genres']['genre'])) {
			$genre = @implode('|', $tvrShow['genres']['genre']);
		} else {
			$genre = $tvrShow['genres']['genre'];
		}
	}
	$country = '';
	if (isset($tvrShow['country']) && !empty($tvrShow['country'])) {
		$country = $tvrage->countryCode($tvrShow['country']);
	}

	$rInfo = $tvrage->getRageInfoFromPage($rageid);
	$desc = '';
	if (isset($rInfo['desc']) && !empty($rInfo['desc'])) {
		$desc = $rInfo['desc'];
	}

	$imgbytes = '';
	if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
		$img = getUrl($rInfo['imgurl']);
		if ($img !== false) {
			$im = @imagecreatefromstring($img);
			if ($im !== false) {
				$imgbytes = $img;
			}
		}
	}
	$db->queryDirect(sprintf("UPDATE tvrage SET description = %s, genre = %s, country = %s, imgdata = %s WHERE rageid = %d", $db->escapeString(substr($desc, 0, 10000)), $db->escapeString(substr($genre, 0, 64)), $db->escapeString($country), $db->escapeString($imgbytes), $rageid));
	$name = $db->query("Select releasetitle from tvrage where rageid = " . $rageid);
	echo $c->primary("Updated: " . $name[0]['releasetitle']);
	$diff = floor((microtime(true) - $starttime) * 1000000);
	if (1000000 - $diff > 0) {
		echo $c->alternate("Sleeping");
		usleep(1000000 - $diff);
	}
}
$tvrage->updateSchedule();
