<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'AmazonProductAPI.php';

// Test if your amazon keys are working.

$s = new Sites();
$site = $s->get();
$pubkey = $site->amazonpubkey;
$privkey = $site->amazonprivkey;
$asstag = $site->amazonassociatetag;
$c = new ColorCLI();
$obj = new AmazonProductAPI($pubkey, $privkey, $asstag);

try{$result = $obj->searchProducts("Adriana Koulias The Seal", AmazonProductAPI::BOOKS, "TITLE");}
catch(Exception $e){$result = false;}

if ($result !== false) {
	print_r($result);
	exit($c->header("\nLooks like it is working alright."));
} else {
	print_r($e);
	exit($c->error("\nThere was a problem attemtping to query amazon. Maybe your keys or wrong, or you are being throttled.\n"));
}
