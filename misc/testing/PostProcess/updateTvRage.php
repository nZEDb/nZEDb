<?php
//This script downloads covert art for Tv Shows -- it is intended to be run at interval, generally after the TvRage database is populated
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;
use nzedb\TvRage;
use nzedb\ReleaseImage;
use nzedb\utility\Text;

$pdo = new Settings();

if (empty($argv[1]) && !in_array($argv[1], ['update', 'check'])) {
	echo PHP_EOL . $pdo->log->error("You must provide an argument.  Use update to try and grab new images and check to reconcile the image directory against the database.") . PHP_EOL;
	exit;
}

$tvrage = new TvRage(['Settings' => $pdo, 'Echo' => true]);

switch ((string)$argv[1]) {
	case 'update':
		updateTvImages($pdo, $tvrage);
		break;
	case 'check':
		checkTvImages($pdo, $tvrage);
		break;
	default:
		exit;
}

// This function checks for TV shows in tvrage_titles missing covers and tries to grab them
function updateTvImages($pdo, $tvrage)
{
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
}

// This function checks for covers in the tvrage folder that aren't tied to a tvrage_title row (hascover = 1)
function checkTvImages($pdo, $tvrage)
{
	$text = new Text();
	$images = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tvrage->imgSavePath), RecursiveIteratorIterator::SELF_FIRST);

	if ($images instanceof \Traversable) {
		$imgCount = iterator_count($images);
		echo PHP_EOL . $pdo->log->header("Matching " . number_format($imgCount) . " files to their RageIDs and setting hascover.") . PHP_EOL;
		$checkCnt = $notReqdCnt = $badRageCnt = 0;

		foreach ($images as $file) {

			$rightCut = $text->cutStringUsingLast('/', $file, "right", false);
			$leftCut = $text->cutStringUsingLast('.', $rightCut, "left", false);
			$check = $pdo->queryOneRow(
								sprintf(
									"SELECT id, hascover
									FROM tvrage_titles
									WHERE rageid = %d",
									$leftCut
								)
			);

			if ($check) {
				if ($check['hascover'] == 0) {
					$pdo->queryExec(
								sprintf(
										"UPDATE tvrage_titles
										SET hascover = 1
										WHERE id = %d",
										$check['id']
								)
					);
					$checkCnt++;
				} else {
					$notReqdCnt++;
				}
			} else {
				$missingRageCnt++;
			}
		}
	}
	echo PHP_EOL . $pdo->log->primary("Updated " . number_format($checkCnt) . " RageIDs. " . number_format($notReqdCnt) . " were already set and " . number_format($missingRageCnt) . " images did not have matching RageIDs in the database.") . PHP_EOL;
}
