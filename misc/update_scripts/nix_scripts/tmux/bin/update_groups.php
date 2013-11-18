<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'consoletools.php';
require_once nZEDb_LIB . 'site.php';

$start = TIME();
$c = new ColorCLI;
$consoleTools = new Consoletools();
$s = new Sites();
$site = $s->get();

// Create the connection here and pass
$nntp = new Nntp();
if ($nntp->doConnect() === false)
	exit($c->error("Unable to connect to usenet."));
if ($site->nntpproxy === "1")
	usleep(500000);

echo $c->header("Getting first/last for all your active groups.");
$data = $nntp->getGroups();
if (PEAR::isError($data))
	exit($c->error("Failed to getGroups() from nntp server."));

if ($site->nntpproxy != "1")
	$nntp->doQuit();

$db = new DB();
$db->queryExec('TRUNCATE TABLE shortgroups');

// Put into an array all active groups
$res = $db->query('SELECT name FROM groups WHERE active = 1');

echo $c->header("Inserting new values into shortgroups table.");

foreach ($data as $newgroup)
{
	if (myInArray($res, $newgroup['group'], 'name'))
	{
		$db->queryInsert(sprintf('INSERT INTO shortgroups (name, first_record, last_record, updated) VALUES (%s, %s, %s, NOW())', $db->escapeString($newgroup['group']), $db->escapeString($newgroup['first']), $db->escapeString($newgroup['last'])));
		echo $c->primary('Updated '.$newgroup['group']);
	}
}
echo $c->header('Running time: '.$consoleTools->convertTimer(TIME() - $start));

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
?>
