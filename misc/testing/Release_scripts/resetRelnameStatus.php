<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';

if (!isset($argv[1]))
	exit("This script resets the bitwise on all releases, so you can rerun fixReleaseNames.php miscsorter etc\nRun this with true to run it.\n");

$db = new DB();
$res = $db->queryExec("UPDATE releases SET bitwise = ((bitwise & ~4)|0), bitwise = ((bitwise & ~8)|0), bitwise = ((bitwise & ~16)|0), bitwise = ((bitwise & ~32)|0), bitwise = ((bitwise & ~64)|0), bitwise = ((bitwise & ~128)|0)");

if ($res->rowCount() > 0)
	exit("Succesfully reset the bitwise of {$res} releases to unprocessed.\n");
else
	exit("No releases to be reset.\n");

?>
