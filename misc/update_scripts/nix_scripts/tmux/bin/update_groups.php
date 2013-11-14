<?php
require_once(dirname(__FILE__).'/../../../config.php');
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/ColorCLI.php');
require_once(WWW_DIR.'lib/consoletools.php');
require_once(WWW_DIR.'lib/site.php');

$start = TIME();
$c = new ColorCLI;
$consoleTools = new Consoletools();
$site = new Sites();

$nntp = new Nntp();
if ($nntp->doConnect() === false)
{
	echo $c->error("Unable to connect to usenet.\n");
	return;
}
echo "Getting first/last for all your active groups\n";
$data = $nntp->getGroups();
if ($site->get()->nntpproxy === false)
	$nntp->doQuit();

if (PEAR::isError($data))
	exit($c->error("Failed to getGroups() from nntp server.\n"));

$db = new DB();
$db->queryExec('TRUNCATE TABLE shortgroups');


// Put into an array all active groups
$res = $db->query('SELECT name FROM groups WHERE active = 1');


echo "Inserting new values into shortgroups table\n";

foreach ($data as $newgroup)
{
	if (myInArray($res, $newgroup['group'], 'name'))
	{
		$db->queryInsert(sprintf('INSERT INTO shortgroups (name, first_record, last_record, updated) VALUES (%s, %s, %s, NOW())', $db->escapeString($newgroup['group']), $db->escapeString($newgroup['first']), $db->escapeString($newgroup['last'])));
		echo 'Updated '.$newgroup['group']."\n";
	}
}
echo 'Running time: '.$consoleTools->convertTimer(TIME() - $start)."\n";

function myInArray($array, $value, $key){
	//loop through the array
	foreach ($array as $val) {
		//if $val is an array cal myInArray again with $val as array input
		if (is_array($val))
		{
			if(myInArray($val,$value,$key))
				return true;
		}
		//else check if the given key has $value as value
		else
		{
			if($array[$key]==$value)
				return true;
		}
	}
	return false;
}
