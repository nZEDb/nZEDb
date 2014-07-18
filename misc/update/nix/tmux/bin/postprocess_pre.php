<?php
require_once dirname(__FILE__) . '/../../../config.php';

$pdo = new \nzedb\db\Settings();
$c = new ColorCLI();

$dateLimit = false;
$titles = 0;

if (isset($argv[1]) && is_numeric($argv[1])) {
	$dateLimit = $argv[1];
}

$predb = new PreDb(true);
$predb->checkPre($dateLimit);

if ($titles > 0) {
	echo $c->header('Fetched ' . $titles . ' new title(s) from predb sources.');
}
