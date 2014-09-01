<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$nntp = new \NNTP(['Settings' => $pdo]);
if ($nntp->doConnect() !== true) {
	exit();
}
$data = $nntp->getGroups();

$res = $pdo->query("SELECT name FROM groups ORDER BY name");

foreach ($data as $newgroup)
{
	if (strstr($newgroup["group"], ".bin") != false && !MyInArray($res, $newgroup["group"], "name") && ($newgroup["last"] - $newgroup["first"]) > 100000)
		$pdo->queryInsert(sprintf("INSERT INTO allgroups (name, first_record, last_record, updated) VALUES (%s, %d, %d, NOW())", $pdo->escapeString($newgroup["group"]), $newgroup["first"], $newgroup["last"]));
}

$grps = $pdo->query("SELECT DISTINCT name FROM allgroups");
foreach ($grps as $grp)
{
	if (!MyInArray($res, $grp, "name"))
	{
	    $data = $pdo->queryOneRow(sprintf("SELECT (MAX(last_record) - MIN(first_record)) AS count, (MAX(last_record) - MIN(first_record))/(MAX(updated)-MIN(updated)) as per_second from allgroups WHERE name = %s", $pdo->escapeString($grp["name"])));
    	if (floor($data["per_second"]*3600) >= 100000)
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


/*
DROP TABLE IF EXISTS allgroups;
CREATE TABLE allgroups (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL DEFAULT "",
  first_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
  last_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
  updated DATETIME DEFAULT NULL,
  PRIMARY KEY  (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_allgroups_id ON allgroups(id);
CREATE INDEX ix_allgroups_name ON allgroups(name);
*/
