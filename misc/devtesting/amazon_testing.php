<?php
require(dirname(__FILE__)."/../../www/config.php");
require_once(WWW_DIR."/lib/amazon.php");
require_once(WWW_DIR."/lib/site.php");

function searchBook($title)
{
	$s = new Sites();
	$site = $s->get();
	$amazon = new AmazonProductAPI($site->amazonpubkey, $site->amazonprivkey, $site->amazonassociatetag);
	$result = $amazon->searchProducts($title, AmazonProductAPI::MP3, "TITLE");
	print_r($result);
}

searchBook("Dimmu Borgir - The Serpentine Offering");
