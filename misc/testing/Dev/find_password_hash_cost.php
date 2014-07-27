<?php
/**
 * This code will benchmark your server to determine how high of a cost you can
 * afford. You want to set the highest cost that you can without slowing down
 * you server too much. 10 is a good baseline, and more is good if your servers
 * are fast enough.
 *
 * Set this number in www/settings.php, the nZEDb_PASSWORD_HASH_COST setting.
 */
$timeTarget = 0.2;

$cost = 9;
do {
	$cost++;
	$start = microtime(true);
	password_hash("test", PASSWORD_DEFAULT, ["cost" => $cost]);
	$end = microtime(true);
} while (($end - $start) < $timeTarget);

echo "Appropriate Cost Found: " . $cost . PHP_EOL;