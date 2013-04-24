<?php
require(dirname(__FILE__)."/../../www/config.php");
require_once(WWW_DIR."/lib/amazon.php");
require_once(WWW_DIR."/lib/site.php");

function searchBook($title)
{
	$s = new Sites();
	$site = $s->get();
	$amazon = new AmazonProductAPI($site->amazonpubkey, $site->amazonprivkey);
	$result = $amazon->searchProducts($title, AmazonProductAPI::MP3, "MUSIC");
	print_r($result);
}

searchBook("Dimmu Borgir - The Serpentine Offering");
