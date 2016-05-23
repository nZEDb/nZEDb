<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;
use nzedb\utility\Misc;
use nzedb\ReleaseExtra;

$pdo = new Settings();
$re = new ReleaseExtra();

$releases = $pdo->queryExec(sprintf('SELECT r.id as releases_id, re.mediainfo as mediainfo from releases r INNER JOIN releaseextrafull re ON r.id = re.releases_id'));
$total = $releases->rowCount();
$count = 0;

echo $pdo->log->header("Updating Unique IDs for " . number_format($total) . " releases.");

foreach ($releases as $release) {
	$xmlObj = @simplexml_load_string($release['mediainfo']);
	$arrXml = Misc::objectsIntoArray($xmlObj);
	if (isset($arrXml['File']) && isset($arrXml['File']['track'])) {
		foreach ($arrXml['File']['track'] as $track) {
			if (isset($track['@attributes']) && isset($track['@attributes']['type'])) {
				if ($track['@attributes']['type'] == 'General') {
					if (isset($track['Unique_ID'])) {
						if (preg_match('/\(0x(?P<hash>[0-9a-f]{32})\)/i', $track['Unique_ID'], $matches)){
							$uniqueid = $matches['hash'];
							$re->addUID($release['releases_id'], $uniqueid);
							$count++;
						}
					}
				}
			}
		}
	}
	echo "$count / $total\r";
}
echo $pdo->log->primary('Added ' . $count . ' Unique IDs');

