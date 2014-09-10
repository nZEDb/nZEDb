<?php
/*
 * This script allows you to (re)set the hashed password for any user account.
 *
 * The main use is for when admin is locked out of the site access for any
 * reason, it will allow the password hash on the account to be changed.
 * Hopefully that will allow admin access to fix any further problems.
 */
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if ($argc < 3) {
	exit(
		$pdo->log->error(
			'Not enough parameters!' . PHP_EOL .
			'Argument 1: New password.' . PHP_EOL .
			'Argument 2: ID or username of the user.' . PHP_EOL
		)
	);
}

$password = $argv[1];
$identifier = $argv[2];
if (is_numeric($password)) {
	exit($pdo->log->error('Password cannot be numbers only!'));
}

$field = (is_numeric($identifier) ? 'id' : 'username');
$user = $pdo->queryOneRow(
	sprintf(
		"SELECT id, username FROM users WHERE %s = %s",
		$field,
		(is_numeric($identifier) ? $identifier : $pdo->escapeString($identifier))
	)
);

if ($user !== false) {
	$users = new \Users(['Settings' => $pdo]);
	$hash = $users->hashPassword($password);
	$result = false;
	if ($hash !== false) {
		$hash = $pdo->queryExec(
			sprintf(
				'UPDATE users SET password = %s WHERE id = %d',
				$hash, $user['id']
			)
		);
	}

	if ($result === false || $hash === false) {
		echo $pdo->log->error('An error occured during update attempt.' . PHP_EOL . $pdo->errorInfo());
	} else {
		echo $pdo->log->headerOver("Updated {$user['username']}'s password hash to: ") . $pdo->log->primary("$hash");
	}
} else {
	echo $pdo->log->error("Unable to find {$field} '{$identifier}' in the users. Cannot change password.");
}
