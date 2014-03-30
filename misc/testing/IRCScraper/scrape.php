<?php

if (!is_file('settings.php')) {
	exit('Copy settings_example.php to settings.php and change the settings.' . PHP_EOL);
}

if (!isset($argv[1])) {
	exit(
		'Argument 1: corrupt|efnet         ; Scrape corrupt, efnet or zenet. Both zenet and corrupt pre the same, so only run one or the other.' . PHP_EOL .
		'                                  ; You can run efnet at the same time as corrupt or zenet.' . PHP_EOL .
		'Argument 2: (optional) false|true ; True runs in silent mode (no text output)' . PHP_EOL .
		'Argument 3: (optional) false|true ; True turns on debug (not recommended)' . PHP_EOL .
		'Argument 4: (optional) false|true ; True uses real sockets(faster), false uses fsock. If you have issues with real sockets, try fsock.' . PHP_EOL .
		'ex:' . PHP_EOL .
		'php ' . $argv[0] . ' efnet                         ; Scrapes efnet with text output.' . PHP_EOL .
		'php ' . $argv[0] . ' corrupt true > /dev/null 2>&1 ; (unix only) Scrapes corrupt with no text output, in the background (you can close your terminal window).' . PHP_EOL .
		'php ' . $argv[0] . ' efnet true                    ; Scrapes efnet with no text output, keeps lock on terminal (closing terminal kills the scraping).' . PHP_EOL .
		'php ' . $argv[0] . ' corrupt true true             ; Scrapes corrupt with text output and debug output.' . PHP_EOL
	);
}

if (!in_array($argv[1], array('efnet', 'corrupt', 'zenet'))) {
	exit('Error, must be efnet, corrupt or zenet, you typed: ' . $argv[1] . PHP_EOL);
}

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'Net_SmartIRC/Net/SmartIRC.php';
require_once 'settings.php';

if (!defined('SCRAPE_IRC_EFNET_NICKNAME') || !defined('SCRAPE_IRC_CORRUPT_NICKNAME') || !defined('SCRAPE_IRC_ZENET_NICKNAME')) {
	exit ('ERROR! You must update your settings.php using settings_example.php' . PHP_EOL);
}

if (SCRAPE_IRC_EFNET_NICKNAME == '' || SCRAPE_IRC_CORRUPT_NICKNAME == '' || SCRAPE_IRC_ZENET_NICKNAME == '') {
	exit("ERROR! You must put a username in settings.php" . PHP_EOL);
}

$silent = ((isset($argv[2]) && $argv[2] === 'true')  ? true : false);
$debug  = ((isset($argv[3]) && $argv[3] === 'true')  ? true : false);
$socket = ((isset($argv[4]) && $argv[4] === 'false') ? false : true);

// Net_SmartIRC started here, or else globals are not properly set.
$scraper = new IRCScraper(new Net_SmartIRC(), $argv[1], $silent, $debug, $socket);