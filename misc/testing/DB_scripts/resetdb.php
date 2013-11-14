<?php
passthru('clear');
echo "This script removes all releases, nzb files, samples, previews , nfos, truncates all article tables and resets all groups.\n";
echo "Are you sure you want reset the DB?  Type 'DESTROY' to continue: \n";
$line = fgets(STDIN);
if(trim($line) != 'DESTROY')
{
	echo "This script is dangerous you must type DESTROY for it function\n";
	exit();
}
echo "\n";
echo "Thank you, continuing...\n";

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'releases.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'consoletools.php';

$db = new Db();
$s = new Sites();
$consoletools = new ConsoleTools();
$site = $s->get();
$timestart = TIME();
$relcount = 0;

echo "Truncating tables.\n";
$db->queryExec("TRUNCATE TABLE collections");
$db->queryExec("TRUNCATE TABLE binaries");
$db->queryExec("TRUNCATE TABLE parts");
$db->queryExec("TRUNCATE TABLE partrepair");
$db->queryExec("TRUNCATE TABLE releasenfo");
$db->queryExec("TRUNCATE TABLE nzbs");

echo "Resetting groups.\n";
$db->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL");

echo "Querying db for releases.\n";
$relids = $db->query(sprintf("SELECT id, guid FROM releases"));
if (count($relids) > 0)
{
	echo "Deleting ".number_format(count($relids))." releases, NZB's, previews and samples.\n";
	$releases = new Releases();

	foreach ($relids as $relid)
	{
		$releases->fastDelete($relid['id'], $relid['guid'], $site);
		$consoletools->overWrite("Deleting:".$consoletools->percentString(++$relcount,sizeof($relids))." Time:".$consoletools->convertTimer(TIME() - $timestart));
	}
}
if ($relcount > 0)
{
	$consoletools = new ConsoleTools();
	echo "\nDeleted ".$relcount." release(s). This script ran for ".$consoletools->convertTime(TIME() - $timestart);
}
