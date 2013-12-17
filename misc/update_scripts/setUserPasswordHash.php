<?php
/*
 * This script allows you to (re)set the hashed password for any user account.
 *
 * The main use is for when admin is locked out of the site access for any
 * reason, it will allow the password hash on the account to be changed.
 * Hopefully that will allow admin access to fix any further problems.
 */
if ($argc < 3) {
	die("Not enough parameters\n  Usage: php {$argv[0]} <new-password> [<user-name> | <userid>]");
}
$password = $argv[1];
$identifier = $argv[2];
if (is_numeric($password)) {
	die("Password cannot be  numbers only!\n");
}

require_once '../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';

$field = is_numeric($identifier) ? 'id' : 'username';
$db = new DB();
$query = "SELECT `id`, `username` FROM users WHERE $field = ";
$query .= is_numeric($identifier) ? $identifier : "'$identifier'";
$result = $db->queryOneRow($query);

if ($result !== false) {
	$hash = crypt($password);		// Let crypt use a random salt.
	$query = "UPDATE `users` SET password = '$hash' WHERE `id` = {$result['id']}";
	$result = $db->queryDirect($query);
	if ($result === false) {
		echo "An error occured during update attempt.\n" . $db->errorInfo();
	} else {
		echo "Updated {$result['username']}'s password hash to\n $hash\n";
	}
} else {
	echo "Unable to find $field '$identifier' in the users. Cannot change password.\n\n";
}
