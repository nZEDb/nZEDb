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

$c = new ColorCLI();

if ($argc < 3)
{
	exit($c->error("\nNot enough parameters\nUsage: php {$argv[0]} <new-password> [<user-name> | <userid>]"));
}

$password = $argv[1];
$identifier = $argv[2];
if (is_numeric($password))
{
	exit($c->error("\nPassword cannot be numbers only!"));
}


$field = is_numeric($identifier) ? 'id' : 'username';
$pdo = new Settings();
$query = "SELECT `id`, `username` FROM users WHERE $field = ";
$query .= is_numeric($identifier) ? $identifier : "'$identifier'";
$resulta = $pdo->queryOneRow($query);

if ($resulta !== false)
{
	$hash = crypt($password);		// Let crypt use a random salt.
	$query = "UPDATE `users` SET password = '$hash' WHERE `id` = {$resulta['id']}";
	$result = $pdo->queryDirect($query);
	if ($result === false)
	{
		echo $c->error("An error occured during update attempt.\n" . $pdo->errorInfo());
	}
	else
	{
		echo $c->headerOver("Updated {$resulta['username']}'s password hash to") . $c->primary("\n$hash");
	}
}
else
{
	echo $c->error("Unable to find $field '$identifier' in the users. Cannot change password.\n");
}
