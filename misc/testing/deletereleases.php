<?php

//This script allows you to delete properly all releases which match some criteria
//The nzb, covers and all linked records will be deleted properly.

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/releases.php");

$releases = new Releases();
$db = new Db;

//
// delete all releases for a group which only has x number of files
//
//$sql = "select * from releases where totalpart = 1 and groupID in (select ID from groups where name = 'alt.binaries.cd.image')";

//
// delete all releases where the only file inside the rars is setup.exe
//
$sql = "select releaseID as ID from ( select releasefiles.releaseID, name, count(*) filenum, totnum from releasefiles left outer join (   select releaseID, count(*) as totnum from releasefiles group by releaseID ) x on x.releaseID = releasefiles.releaseID where releasefiles.name = 'setup.exe' group by releasefiles.releaseID, name ) y where y.filenum = y.totnum"; 

$rel = $db->query($sql);
echo "about to delete ".count($rel)." release(s)";

foreach ($rel as $r) 
{
	$releases->delete($r['ID']);
}

?>