<?php
// Shitty script to check time/date in php mysql and system...
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;
use nzedb\utility;

$n = PHP_EOL;

// TODO change this to be able to use GnuWin
if (!nzedb\utility\Utility::isWin()) {
	echo 'These are the settings in your php.ini files:' . $n;
	echo 'CLI PHP timezone : ' . exec('cat /etc/php5/cli/php.ini | grep \'date.timezone =\' | cut -d \  -f 3') . $n;
	echo 'apache2 timezone : ' . exec('cat /etc/php5/apache2/php.ini| grep \'date.timezone =\' | cut -d \  -f 3') . $n;
}

$system = ' not supported on windows.';

$pdo = new Settings();
$MySQL = $pdo->queryOneRow('SELECT NOW() AS time, @@system_time_zone AS tz');
if (!nzedb\utility\Utility::isWin()) {
	$system = exec('date');
}
$php = date('D M d H:i:s T o');
$MySQL = date('D M d H:i:s T o', strtotime($MySQL['time'] . ' ' . $MySQL['tz']));

echo 'The various dates/times:' . $n;
echo 'System time      : ' . $system . $n;
echo 'MYSQL time       : ' . $MySQL . $n;
echo 'PHP time         : ' . $php . $n;

if ($MySQL === $system && $system === $php) {
	exit('Looks like all your dates/times are good.' . $n);
} else {
	exit('Looks like you have 1 or more dates/times set wrong.' . $n);
}
