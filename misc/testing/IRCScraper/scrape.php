<?php
$settingsFile = 'settings.php';
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'Net_SmartIRC/Net/SmartIRC.php';
require_once $settingsFile;
if (SCRAPE_IRC_SERVER === '' ||
	SCRAPE_IRC_PORT === '' ||
	SCRAPE_IRC_NICKNAME === '' ||
	SCRAPE_IRC_REALNAME === '' ||
	SCRAPE_IRC_PASSWORD === '' ||
	SCRAPE_IRC_USERNAME === ''
) {
	exit ('One of your settings in ' . $settingsFile . ' is not set.');
}

$scraper = new IRCScraper();
// Net_SmartIRC started here, or else globals are not properly set.
$scraper->startScraping(new Net_SmartIRC());