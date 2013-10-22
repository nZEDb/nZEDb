<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/nntp.php");

$nntp = new Nntp();
$nntp->doConnect();

echo "Getting first/last for all active groups\n";
$data = $nntp->getGroups();

$nntp->doQuit();

echo "Inserting new values into shortgroups table\n";
$db = new DB();
$db->queryExec("TRUNCATE TABLE shortgroups");
foreach ($data as $newgroup)
{
	$res1 = $db->queryOneRow(sprintf('SELECT name FROM groups WHERE name = %s', $db->escapeString($newgroup['group'])));
	if (isset($res1['name']))
		$db->queryInsert(sprintf("INSERT INTO shortgroups (name, first_record, last_record, updated) VALUES (%s, %s, %s, NOW())", $db->escapeString($newgroup["group"]), $db->escapeString($newgroup["first"]), $db->escapeString($newgroup["last"])));
}

