<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI();


if (!isset($argv[1]))
{
	passthru('clear');
	exit($c->error("\nThis script sets bitwise = 0 or just resets specific bits.\nTo reset all releases, run:\nphp resetRelnameStatus.php true\n\nTo run on specific bit, run:\nphp resetRelnameStatus.php 512\n\n"));
}

$db = new DB();
if ($argv[1] === 'true')
	$res = $db->queryExec('UPDATE releases SET bitwise = 0');
else if (is_numeric($argv[1]))
	$res = $db->queryExec('UPDATE releases SET bitwise = ((bitwise & ~'.$argv[1].')|0)');

if ($res->rowCount() > 0 && is_numeric($argv[1]))
	echo $c->header('Succesfully reset the bitwise of '.$res->rowCount().' releases to 0 for bit(s) '.$argv[1].'.');
else if ($res->rowCount() > 0)
	echo $c->header('Succesfully reset the bitwise of '.$res->rowCount().' releases to unprocessed.');
else
	echo $c->header('No releases to be reset.');

?>
