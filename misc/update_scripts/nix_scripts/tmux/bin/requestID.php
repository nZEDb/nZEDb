<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'backfill.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'page.php';
require_once nZEDb_LIB . 'category.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from update_threaded.py."));

$pieces = explode('                       ', $argv[1]);
$db = new DB();
$page = new Page();
$n = "\n";
$category = new Category();
$groups = new Groups();

if (!preg_match('/^\[\d+\]/', $pieces[1]))
{
	$db->queryExec('UPDATE releases SET reqidstatus = -2 WHERE id = ' . $pieces[0]);
	exit('.');
}

$requestIDtmp = explode(']', substr($pieces[1], 1));
$bFound = false;
$newTitle = '';
$updated = 0;

if (count($requestIDtmp) >= 1)
{
	$requestID = (int) $requestIDtmp[0];
	if ($requestID != 0 and $requestID != '')
	{
		// Do a local lookup first
		$newTitle = localLookup($requestID, $pieces[2], $pieces[1]);
		if ($newTitle != false && $newTitle != '')
		{
			$bFound = true;
			$local = true;
		}
		else
		{
			$newTitle = getReleaseNameFromRequestID($page->site, $requestID, $pieces[2]);
			if ($newTitle != false && $newTitle != '')
			{
				$bFound = true;
				$local = false;
			}
		}
	}
}

if ($bFound === true)
{
	$groupname = $groups->getByNameByID($pieces[2]);
	$determinedcat = $category->determineCategory($newTitle, $groupname);
	$run = $db->prepare(sprintf('UPDATE releases set reqidstatus = 1, bitwise = ((bitwise & ~4)|4), searchname = %s, categoryid = %d where id = %d', $db->escapeString($newTitle), $determinedcat, $pieces[0]));
	$run->execute();
	$newcatname = $category->getNameByID($determinedcat);
	$method = ($local === true) ? 'requestID local' : 'requestID web';

	echo 	$c->headerOver($n.$n.'New name:  ').$c->primary($newTitle).
			$c->headerOver('Old name:  ').$c->primary($pieces[1]).
			$c->headerOver('New cat:   ').$c->primary($newcatname).
			$c->headerOver('Group:     ').$c->primary($pieces[2]).
			$c->headerOver('Method:    ').$c->primary($method).
			$c->headerOver('ReleaseID: ').$c->primary($pieces[0]);
	$updated++;
}
else
{
	$db->queryExec('UPDATE releases SET reqidstatus = -2 WHERE id = ' . $pieces[0]);
	echo '.';
}

function getReleaseNameFromRequestID($site, $requestID, $groupName)
{
	if ($site->request_url == '')
		return '';

	// Build Request URL
	$req_url = str_ireplace('[GROUP_NM]', urlencode($groupName), $site->request_url);
	$req_url = str_ireplace('[REQUEST_ID]', urlencode($requestID), $req_url);

	$xml = simplexml_load_file($req_url);

	if (($xml == false) || (count($xml) == 0))
		return '';

	$request = $xml->request[0];

	return (!isset($request) || !isset($request['name'])) ? '' : $request['name'];
}

function localLookup($requestID, $groupName, $oldname)
{
	$db = new DB();
	$groups = new Groups();
	$groupid = $groups->getIDByName($groupName);
	$run = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
	if (isset($run['title']))
		return $run['title'];
	if (preg_match('/\[#?a\.b\.teevee\]/', $oldname))
		$groupid = $groups->getIDByName('alt.binaries.teevee');
	else if (preg_match('/\[#?a\.b\.moovee\]/', $oldname))
		$groupid = $groups->getIDByName('alt.binaries.moovee');
	else if (preg_match('/\[#?a\.b\.erotica\]/', $oldname))
		$groupid = $groups->getIDByName('alt.binaries.erotica');
	else if (preg_match('/\[#?a\.b\.foreign\]/', $oldname))
		$groupid = $groups->getIDByName('alt.binaries.mom');
	else if ($groupName == 'alt.binaries.etc')
		$groupid = $groups->getIDByName('alt.binaries.teevee');
	
	$run = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
	if (isset($run['title']))
		return $run['title'];
}
?>
