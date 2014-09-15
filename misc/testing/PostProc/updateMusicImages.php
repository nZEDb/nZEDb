<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$covers = $updated = $deleted = 0;

if ($argc == 1 || $argv[1] != 'true') {
    exit($pdo->log->error("\nThis script will check all images in covers/music and compare to db->musicinfo.\nTo run:\nphp $argv[0] true\n"));
}

$row = $pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'coverspath'");
if ($row !== false) {
	\nzedb\utility\Utility::setCoversConstant($row['value']);
} else {
	die("Unable to set Covers' constant!\n");
}
$path2covers = nZEDb_COVERS . 'music' . DS;

$dirItr = new \RecursiveDirectoryIterator($path2covers);
$itr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($itr as $filePath) {
    if (is_file($filePath) && preg_match('/\d+\.jpg/', $filePath)) {
        preg_match('/(\d+)\.jpg/', basename($filePath), $match);
        if (isset($match[1])) {
            $run = $pdo->queryDirect("UPDATE musicinfo SET cover = 1 WHERE cover = 0 AND id = " . $match[1]);
            if ($run->rowCount() >= 1) {
                $covers++;
            } else {
                $run = $pdo->queryDirect("SELECT id FROM musicinfo WHERE id = " . $match[1]);
                if ($run->rowCount() == 0) {
                    echo $pdo->log->info($filePath . " not found in db.");
                }
            }
        }
    }
}

$qry = $pdo->queryDirect("SELECT id FROM musicinfo WHERE cover = 1");
if ($qry instanceof \Traversable) {
	foreach ($qry as $rows) {
		if (!is_file($path2covers . $rows['id'] . '.jpg')) {
			$pdo->queryDirect("UPDATE musicinfo SET cover = 0 WHERE cover = 1 AND id = " . $rows['id']);
			echo $pdo->log->info($path2covers . $rows['id'] . ".jpg does not exist.");
			$deleted++;
		}
	}
}
echo $pdo->log->header($covers . " covers set.");
echo $pdo->log->header($deleted . " music unset.");
