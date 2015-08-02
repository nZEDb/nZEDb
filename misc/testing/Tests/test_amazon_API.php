<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');
require_once nZEDb_LIBS . 'AmazonProductAPI.php';

use nzedb\db\Settings;

// Test if your amazon keys are working.

$pdo = new Settings();
$pubkey = $pdo->getSetting('amazonpubkey');
$privkey = $pdo->getSetting('amazonprivkey');
$asstag = $pdo->getSetting('amazonassociatetag');
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
