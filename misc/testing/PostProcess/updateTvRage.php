<?php
//This script downloads covert art for Tv Shows -- it is intended to be run at interval, generally after the TvRage database is populated
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;
use nzedb\TvRage;
use nzedb\ReleaseImage;

$pdo = new Settings();
$tvrage = new TvRage(['Settings' => $pdo, 'Echo' => true]);

$shows = $pdo->queryDirect("SELECT rageid, releasetitle FROM tvrage_titles WHERE hascover = 0 ORDER BY rageid DESC LIMIT 2000");
if ($shows->rowCount() > 0) {
	echo "\n";
	echo $pdo->log->header("Updating " . number_format($shows->rowCount()) . " tv shows.\n");
} else {
	echo "\n";
	echo $pdo->log->info("All shows in TvRage database have been updated.\n");
	usleep(5000000);
}

if ($shows instanceof \Traversable) {
	foreach ($shows as $show) {
		$starttime = microtime(true);
		$showid = $show['id'];
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

		$hasCover = 0;

		if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl'])) {
			$hasCover = (new ReleaseImage($pdo))->saveImage($rageid, $rInfo['imgurl'], $tvrage->imgSavePath, '', '');
		}

		$pdo->queryDirect(
				sprintf("
					UPDATE tvrage_titles
					SET description = %s, genre = %s, country = %s, hascover = %d
					WHERE rageid = %d",
					$pdo->escapeString(substr($desc, 0, 10000)),
					$pdo->escapeString(substr($genre, 0, 64)),
					$pdo->escapeString($country),
					$hasCover,
					$rageid
				)
		);

		echo $pdo->log->primary("Updated: " . $show['releasetitle']);
		$diff = floor((microtime(true) - $starttime) * 1000000);
		if (1000000 - $diff > 0) {
			echo $pdo->log->alternate("Sleeping");
			usleep(1000000 - $diff);
		}
	}
}
