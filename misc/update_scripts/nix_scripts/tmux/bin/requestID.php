<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/page.php");

$pieces = explode("                       ", $argv[1]);

$db = new DB();
$page = new Page();
$n = "\n";


$requestIDtmp = explode("]", substr($pieces[1], 1));
$bFound = false;
$newTitle = "";

if (count($requestIDtmp) >= 1)
{
	$requestID = (int) $requestIDtmp[0];
	if ($requestID != 0)
	{
		$newTitle = getReleaseNameFromRequestID($page->site, $requestID, $pieces[2]);
		if ($newTitle != false && $newTitle != "")
		{
			$bFound = true;
			$iFoundcnt++;
		}
	}
}

if ($bFound)
{
	$db->query("UPDATE releases SET reqidstatus = 1, searchname = " . $db->escapeString($newTitle) . " WHERE ID = " . $pieces[0]);
	echo "Updated requestID " . $requestID . " to release name: ".$newTitle.$n;
}					
else
{
	$db->query("UPDATE releases SET reqidstatus = -2 WHERE ID = " . $pieces[0]);
	echo ".";
}

function getReleaseNameFromRequestID($site, $requestID, $groupName)
{
	if ($site->request_url == "")
		return "";
	
	// Build Request URL
	$req_url = str_ireplace("[GROUP_NM]", urlencode($groupName), $site->request_url);
	$req_url = str_ireplace("[REQUEST_ID]", urlencode($requestID), $req_url);
	
	$xml = simplexml_load_file($req_url);
	
	if (($xml == false) || (count($xml) == 0))
		return "";
		
	$request = $xml->request[0];

	return (!isset($request) || !isset($request["name"])) ? "" : $request['name'];
}		
