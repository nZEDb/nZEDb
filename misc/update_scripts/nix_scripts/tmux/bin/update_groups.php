<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/nntp.php");

$db = new DB();
$res = $db->prepare("SELECT name FROM groups WHERE active = 1");
$res->execute();
$total = $res->rowCount();
echo "Updating first and last from ".$total." groups.\n";

$nntp = new Nntp();
$nntp->doConnect();
$data = $nntp->getGroups();
$nntp->doQuit();
if ($total > 0)
{
	foreach ($data as $newgroup)
	{
		if (MyInArray($res, $newgroup["group"], "name") == true)
			$db->queryInsert(sprintf("INSERT INTO allgroups (name, first_record, last_record, updated) VALUES (%s, %d, %d, NOW())", $db->escapeString($newgroup["group"]), $newgroup["first"], $newgroup["last"]));
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
