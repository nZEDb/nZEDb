<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/predb.php");
require_once(WWW_DIR."lib/nzbcontents.php");

$db = new DB();
$namefixer = new Namefixer(true);

if (isset($argv[1]))
{
	$pieces = explode(" ", $argv[1]);
	if (isset($pieces[1]) && $pieces[0] == "nfo")
	{
		$release = $pieces[1];
		if ($res = $db->queryOneRow(sprintf("SELECT rel.guid AS guid, nfo.releaseid AS nfoid, rel.groupid, rel.categoryid, rel.searchname, uncompress(nfo) AS textstring, rel.id AS releaseid FROM releases rel INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) WHERE rel.id = %d", $release)))
		{
			//ignore encrypted nfos
			if (preg_match('/^=newz\[NZB\]=\w+/', $res['textstring']))
			{
				$namefixer->done = $namefixer->matched = false;
				$fail = $db->prepare(sprintf("UPDATE releases SET relnamestatus = 20 WHERE id = %d", $res['releaseid']));
				$fail->execute();
				$namefixer->checked++;
				echo ".";
			}
			else
			{
				//echo $res['textstring']."\n";
				$namefixer->done = $namefixer->matched = false;
				$namefixer->checkName($res, $echo=true, $type="NFO, ", $namestatus="1");
				$namefixer->checked++;
				echo ".";
			}
		}
	}

	if (isset($pieces[1]) && $pieces[0] == "filename")
	{
		$release = $pieces[1];
		if ($res = $db->queryOneRow(sprintf("SELECT relfiles.name AS textstring, rel.categoryid, rel.searchname, rel.groupid, relfiles.releaseid AS fileid, rel.id AS releaseid FROM releases rel INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) WHERE rel.id = %d", $release)))
		{
			//echo $res['textstring']."\n";
			$namefixer->done = $namefixer->matched = false;
			$namefixer->checkName($res, $echo=true, $type="Filenames, ", $namestatus="1");
			$namefixer->checked++;
			echo ".";
		}
	}

	if (isset($pieces[1]) && $pieces[0] == "md5")
	{
		$release = $pieces[1];
		if ($res = $db->queryOneRow(sprintf("SELECT r.id, r.name, r.searchname, r.categoryid, r.groupid, rf.name AS filename FROM releases r LEFT JOIN releasefiles rf ON r.id = rf.releaseid WHERE r.id = %d", $release)))
		{
			if (preg_match("/[a-f0-9]{32}/i", $res["name"], $matches))
				$namefixer->matchPredbMD5($matches[0], $res, $echo=1, $namestatus=1, $echooutput=true);
			elseif (preg_match("/[a-f0-9]{32}/i", $res["filename"], $matches))
				$namefixer->matchPredbMD5($matches[0], $res, $echo=1, $namestatus=1, $echooutput=true);
			echo ".";
		}
	}

	if (isset($pieces[1]) && $pieces[0] == "par2")
	{
		$relID = $pieces[1];
		$guid = $pieces[2];
		$groupID = $pieces[3];
		$nzbcontents = new NZBcontents(true);
		$pp = new Postprocess($echooutput=true);
		$nzbcontents->checkPAR2($guid, $relID, $groupID, $db, $pp);
		echo ".";
	}
}
