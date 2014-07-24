<?php
require_once dirname(__FILE__) . '/../../../config.php';

$pdo = new \nzedb\db\Settings();
$c = new ColorCLI();

$dateLimit = false;

if (isset($argv[1]) && is_numeric($argv[1])) {
	$dateLimit = $argv[1];
}

$predb = new PreDb(true);
$predb->checkPre($dateLimit);

exit;
