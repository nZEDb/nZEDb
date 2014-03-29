<?php

if (!isset($argv[1])) {
	exit('Type in corrupt or efnet for the type of server to scrape, you can run this script 2 times to scrape both at the same time.' . PHP_EOL);
}

if (!in_array($argv[1], array('efnet', 'corrupt'))) {
	exit('Error, must be efnet or corrupt, you typed: ' . $argv[1] . PHP_EOL);
}

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'Net_SmartIRC/Net/SmartIRC.php';
require_once 'settings.php';

if (SCRAPE_IRC_EFNET_NICKNAME == '' || SCRAPE_IRC_CORRUPT_NICKNAME == '') {
	exit("ERROR! You must put a username in settings.php" . PHP_EOL);
}

$scraper = new IRCScraper();
// Net_SmartIRC started here, or else globals are not properly set.
$scraper->startScraping(new Net_SmartIRC(), $argv[1]);