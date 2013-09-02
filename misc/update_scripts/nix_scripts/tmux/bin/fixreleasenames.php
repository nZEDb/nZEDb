<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/predb.php");

$db = new DB();
$namefixer = new Namefixer();

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	if (isset($pieces[1]) && $pieces[0] == "nfo")
	{
		$release = $pieces[1];
		if ($res = $db->query(sprintf("SELECT rel.guid AS guid, nfo.releaseid AS nfoid, rel.groupid, rel.categoryid, rel.searchname, uncompress(nfo) AS textstring, rel.id AS releaseid FROM releases rel INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) WHERE rel.id = %d", $release)))
		{
			foreach ($res as $rel)
			{
				//echo $rel['textstring']."\n";
				$namefixer->done = $namefixer->matched = false;
				$namefixer->checkName($rel, $echo=true, $type="NFO, ", $namestatus="1");
				$namefixer->checked++;
			}
			echo ".";
		}
	}
	if (isset($pieces[1]) && $pieces[0] == "filename")
	{
		$release = $pieces[1];
		if ($res = $db->query(sprintf("SELECT relfiles.name AS textstring, rel.categoryid, rel.searchname, rel.groupid, relfiles.releaseid AS fileid, rel.id AS releaseid FROM releases rel INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) WHERE rel.id = %d", $release)))
		{
			foreach ($res as $rel)
			{
				//echo $rel['textstring']."\n";
				$namefixer->done = $namefixer->matched = false;
				$namefixer->checkName($rel, $echo=true, $type="Filenames, ", $namestatus="1");
				$namefixer->checked++;
			}
			echo ".";
		}
	}
	if (isset($pieces[1]) && $pieces[0] == "md5")
	{
		$release = $pieces[1];
		if ($res = $db->query(sprintf("SELECT r.id, r.name, r.searchname, r.categoryid, r.groupid, rf.name AS filename FROM releases r LEFT JOIN releasefiles rf ON r.id = rf.releaseid WHERE r.id = %d", $release)))
		{
			foreach ($res as $rel)
			{
				if (preg_match("/[a-f0-9]{32}/i", $rel["name"], $matches))
					$namefixer->matchPredbMD5($matches[0], $rel, $echo=true, $namestatus="1", $echooutput=true);
				else if (preg_match("/[a-f0-9]{32}/i", $rel["filename"], $matches))
					$namefixer->matchPredbMD5($matches[0], $rel, $echo=true, $namestatus="1", $echooutput=true);
			}
			echo ".";
		}
	}
}
