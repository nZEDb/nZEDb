<?php
/**
 * This code will benchmark your server to determine how high of a cost you can
 * afford. You want to set the highest cost that you can without slowing down
 * you server too much. 10 is a good baseline, and more is good if your servers
 * are fast enough.
 *
 * Set this number in www/settings.php, the nZEDb_PASSWORD_HASH_COST setting.
 */

if (!isset($argv[1]) || !is_numeric($argv[1]) || $argv[1] < 0.05) {
	exit(
		'You can pass in a target time, which will be used to determine the cost.' . PHP_EOL .
		'The target time is the amount of time it will take to hash a password.' . PHP_EOL .
		'Hashing of passwords happens when a user registers an account or their hash needs to be updated because it is insecure.' . PHP_EOL .
		'Values between 0.2 and 0.5 are recommended. The minimum is 0.05 for security reasons.' . PHP_EOL
	);
}
$timeTarget = $argv[1];

$cost = 7;
do {
	$cost++;
	$start = microtime(true);
	password_hash("test", PASSWORD_DEFAULT, ["cost" => $cost]);
	$end = microtime(true);
} while (($end - $start) < $timeTarget);

echo "Appropriate Cost Found: " . $cost . PHP_EOL;
