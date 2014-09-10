<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (!isset($argv[1])) {
	exit($pdo->log->error("\nThis script gets all binary groups from usenet and compares against yours.\n\n"
		. "php $argv[0] 1000000   ...: To show all groups you do not have with more than 1000000 posts per hour.\n"));
}

$nntp = new \NNTP(['Settings' => $pdo]);
if ($nntp->doConnect() !== true) {
	exit();
}
$data = $nntp->getGroups();

if ($nntp->isError($data)) {
	exit($pdo->log->error("\nFailed to getGroups() from nntp server.\n"));
}
if (!isset($data['group'])) {
	exit($pdo->log->error("\nFailed to getGroups() from nntp server.\n"));
}
$nntp->doQuit();

$res = $pdo->query("SELECT name FROM groups");
$counter = 0;
$minvalue = $argv[1];

foreach ($data as $newgroup) {
	if (isset($newgroup["group"])) {
		if (strstr($newgroup["group"], ".bin") != false && MyInArray($res, $newgroup["group"], "name") == false && ($newgroup["last"] - $newgroup["first"]) > 1000000)
			$pdo->queryInsert(sprintf("INSERT INTO allgroups (name, first_record, last_record, updated) VALUES (%s, %d, %d, NOW())", $pdo->escapeString($newgroup["group"]), $newgroup["first"], $newgroup["last"]));
	}
}

$grps = $pdo->query("SELECT DISTINCT name FROM allgroups WHERE name NOT IN (SELECT name FROM groups)");
foreach ($grps as $grp) {
	if (!myInArray($res, $grp, "name")) {
		$data = $pdo->queryOneRow(sprintf("SELECT (MAX(last_record) - MIN(first_record)) AS count, (MAX(last_record) - MIN(last_record))/(UNIX_TIMESTAMP(MAX(updated))-UNIX_TIMESTAMP(MIN(updated))) as per_second, (MAX(last_record) - MIN(last_record)) AS tracked, MIN(updated) AS firstchecked from allgroups WHERE name = %s", $pdo->escapeString($grp["name"])));
		if (floor($data["per_second"]*3600) >= $minvalue) {
			echo $pdo->log->header($grp["name"]);
			echo $pdo->log->primary("Available Post Count: ".number_format($data["count"])."\n"
				."Date First Checked:   ".$data["firstchecked"]."\n"
				."Posts Since First:    ".number_format($data["tracked"])."\n"
				."Average Per Hour:     ".number_format(floor($data["per_second"]*3600))."\n");
			$counter++;
		}
	}
}

if ($counter == 0) {
	echo $pdo->log->info("No groups currently exceeding ".number_format($minvalue)." posts per hour. Try again in a few minutes.");
}

function myInArray($array, $value, $key){
	//loop through the array
	foreach ($array as $val) {
		//if $val is an array cal myInArray again with $val as array input
		if(is_array($val)){
			if(myInArray($val,$value,$key))
				return true;
		} else {
			//else check if the given key has $value as value
			if($array[$key]==$value) {
				return true;
			}
		}
	}
	return false;
}
