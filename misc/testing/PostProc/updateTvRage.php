<?php
//This script downloads covert art for Tv Shows -- it is intended to be run at interval, generally after the TvRage database is populated
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;
use nzedb\utility;

$pdo = new Settings();
$tvrage = new \TvRage(['Settings' => $pdo, 'Echo' => true]);

$shows = $pdo->queryDirect("SELECT rageid FROM tvrage WHERE imgdata IS NULL ORDER BY rageid DESC LIMIT 2000");
if ($shows->rowCount() > 0) {
	echo "\n";
	echo $pdo->log->header("Updating " . number_format($shows->rowCount()) . " tv shows.\n");
} else {
	echo "\n";
	echo $pdo->log->info("All shows in TvRage database have been updated.\n");
	usleep(5000000);
}
$loop = 0;
if ($shows instanceof \Traversable) {
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
			$img = nzedb\utility\Utility::getUrl(['url' => $rInfo['imgurl']]);
			if ($img !== false) {
				$im = @imagecreatefromstring($img);
				if ($im !== false) {
					$imgbytes = $img;
				}
			}
		}
		$pdo->queryDirect(sprintf("UPDATE tvrage SET description = %s, genre = %s, country = %s, imgdata = %s WHERE rageid = %d", $pdo->escapeString(substr($desc, 0, 10000)), $pdo->escapeString(substr($genre, 0, 64)), $pdo->escapeString($country), $pdo->escapeString($imgbytes), $rageid));
		$name = $pdo->query("Select releasetitle from tvrage where rageid = " . $rageid);
		echo $pdo->log->primary("Updated: " . $name[0]['releasetitle']);
		$diff = floor((microtime(true) - $starttime) * 1000000);
		if (1000000 - $diff > 0) {
			echo $pdo->log->alternate("Sleeping");
			usleep(1000000 - $diff);
		}
	}
}
