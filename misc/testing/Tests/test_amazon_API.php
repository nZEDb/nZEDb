<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use app\models\Settings;
use libs\AmazonProductAPI;
use nzedb\db\DB;

// Test if your amazon keys are working.

$pdo = new DB();
$pubkey =	Settings::value('APIs..amazonpubkey');
$privkey =	Settings::value('APIs..amazonprivkey');
$asstag =	Settings::value('APIs..amazonassociatetag');
$obj = new AmazonProductAPI($pubkey, $privkey, $asstag);

$e = null;

try {
	$result = $obj->searchProducts("Adriana Koulias The Seal", AmazonProductAPI::BOOKS, "TITLE");
} catch (Exception $e) {
	$result = false;
}

if ($result !== false) {
	print_r($result);
	exit($pdo->log->header("\nLooks like it is working alright."));
} else {
	print_r($e);
	exit($pdo->log->error("\nThere was a problem attemtping to query amazon. Maybe your keys or wrong, or you are being throttled.\n"));
}
