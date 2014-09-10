<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'AmazonProductAPI.php';

// Test if your amazon keys are working.

$pdo = new \nzedb\db\Settings();
$pubkey = $pdo->getSetting('amazonpubkey');
$privkey = $pdo->getSetting('amazonprivkey');
$asstag = $pdo->getSetting('amazonassociatetag');
$obj = new \AmazonProductAPI($pubkey, $privkey, $asstag);

$e = null;

try{$result = $obj->searchProducts("Adriana Koulias The Seal", \AmazonProductAPI::BOOKS, "TITLE");}
catch(Exception $e){$result = false;}

if ($result !== false) {
	print_r($result);
	exit($pdo->log->header("\nLooks like it is working alright."));
} else {
	print_r($e);
	exit($pdo->log->error("\nThere was a problem attemtping to query amazon. Maybe your keys or wrong, or you are being throttled.\n"));
}
