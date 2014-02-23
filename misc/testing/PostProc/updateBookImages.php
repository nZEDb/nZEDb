<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

$db = new DB();
$covers = $updated = $deleted = 0;
$c = new ColorCLI();

if ($argc == 1 || $argv[1] != 'true') {
    exit($c->error("\nThis script will check all images in covers/book and compare to db->bookinfo.\nTo run:\nphp $argv[0] true\n"));
}

$dirItr = new RecursiveDirectoryIterator(nZEDb_ROOT . 'www/covers/book/');
$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($itr as $filePath) {
    if (is_file($filePath) && preg_match('/\d+\.jpg/', $filePath)) {
        preg_match('/(\d+)\.jpg/', basename($filePath), $match);
        if (isset($match[1])) {
            $run = $db->queryDirect("UPDATE bookinfo SET cover = 1 WHERE cover = 0 AND id = " . $match[1]);
            if ($run->rowCount() >= 1) {
                $covers++;
            } else {
                $run = $db->queryDirect("SELECT id FROM bookinfo WHERE id = " . $match[1]);
                if ($run->rowCount() == 0) {
                    echo $c->info($filePath . " not found in db.");
                }
            }
        }
    }
}

$qry = $db->queryDirect("SELECT id FROM bookinfo WHERE cover = 1");
foreach ($qry as $rows) {
    if (!is_file('/var/www/nZEDb/www/covers/book/' . $rows['id'] . '.jpg')) {
        $db->queryDirect("UPDATE bookinfo SET cover = 0 WHERE cover = 1 AND id = " . $rows['id']);
        echo $c->info('/var/www/nZEDb/www/covers/book/' . $rows['id'] . ".jpg does not exist.");
        $deleted++;
    }
}
echo $c->header($covers . " covers set.");
echo $c->header($deleted . " books unset.");
