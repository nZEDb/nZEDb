<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/nntp.php");

if (!isset($argv[1]))
    exit("This script gets all binary groups from usenet and compares against yours.\nTo run: \ncheck_usenet_groups.php true\n");

$nntp = new Nntp();
$nntp->doConnect();
$data = $nntp->getGroups();

$db = new DB();
$res = $db->query("SELECT name FROM groups");

foreach ($data as $newgroup)
{
	if (strstr($newgroup["group"], ".bin") != false && MyInArray($res, $newgroup["group"], "name") == false && ($newgroup["last"] - $newgroup["first"]) > 1000000)
		$db->queryInsert(sprintf("INSERT INTO allgroups (name, first_record, last_record, updated) VALUES (%s, %d, %d, NOW())", $db->escapeString($newgroup["group"]), $newgroup["first"], $newgroup["last"]));
}

$grps = $db->query("SELECT DISTINCT name FROM allgroups WHERE name NOT IN (SELECT name FROM groups)");
foreach ($grps as $grp)
{
	if (!MyInArray($res, $grp, "name"))
	{
	    $data = $db->queryOneRow(sprintf("SELECT (MAX(last_record) - MIN(first_record)) AS count, (MAX(last_record) - MIN(first_record))/(MAX(updated)-MIN(updated)) as per_second from allgroups WHERE name = %s", $db->escapeString($grp["name"])));
    	if (floor($data["per_second"]*3600) >= 1000000)
        	echo $grp["name"]." has ".number_format($data["count"])." headers available, averaging ".number_format(floor($data["per_second"]*3600))." per hour\n";
	}
}


function myInArray($array, $value, $key){
	//loop through the array
	foreach ($array as $val) {
		//if $val is an array cal myInArray again with $val as array input
		if(is_array($val)){
			if(myInArray($val,$value,$key))
				return true;
		}
		//else check if the given key has $value as value
		else{
			if($array[$key]==$value)
				return true;
		}
	}
	return false;
}
