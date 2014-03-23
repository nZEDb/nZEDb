<?php

require_once dirname(__FILE__) . '/../../../config.php';
$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from requestid_threaded.py."));
}
$pieces = explode('                       ', $argv[1]);
$db = new DB();
$s = new Sites();
$site = $s->get();
$n = "\n";
$category = new Category();
$groups = new Groups();
if (!preg_match('/^\[\d+\]/', $pieces[1])) {
	$db->queryExec('UPDATE releases SET reqidstatus = -2 WHERE id = ' . $pieces[0]);
	exit('.');
}
$requestIDtmp = explode(']', substr($pieces[1], 1));
$bFound = false;
$newTitle = '';
$updated = 0;
if (count($requestIDtmp) >= 1) {
	$requestID = (int) $requestIDtmp[0];
	if ($requestID != 0 and $requestID != '') {
		// Do a local lookup first
		$newTitle = localLookup($requestID, $pieces[2], $pieces[1]);
		if (is_array($newTitle) && $newTitle['title'] != '') {
			$bFound = true;
			$local = true;
		} else {
			$newTitle = getReleaseNameFromRequestID($site, $requestID, $pieces[2]);
			if (is_array($newTitle) && $newTitle['title'] != '') {
				$bFound = true;
				$local = false;
			}
		}
	}
}
if ($bFound === true) {
	$title = $newTitle['title'];
	$preid = $newTitle['id'];
	$groupname = $groups->getByNameByID($pieces[2]);
	$determinedcat = $category->determineCategory($title, $groupname);
	$run = $db->queryDirect(sprintf("UPDATE releases set rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
			. "preid = %d, reqidstatus = 1, isrenamed = 1, searchname = %s, categoryid = %d where id = %d", $preid, $db->escapeString($title), $determinedcat, $pieces[0]));
	$groupid = $groups->getIDByName($pieces[2]);
	if ($groupid !== 0) {
		$md5 = md5($title);
		$db->queryDirect(sprintf("INSERT IGNORE INTO predb (title, adddate, source, md5, requestid, groupid) VALUES "
				. "(%s, now(), %s, %s, %s, %d) ON DUPLICATE KEY UPDATE requestid = %d", $db->escapeString($title), $db->escapeString('requestWEB'), $db->escapeString($md5), $requestID, $groupid, $requestID));
	} else if ($groupid === 0) {
		echo $requestID . "\n";
	}
	$newcatname = $category->getNameByID($determinedcat);
	$method = ($local === true) ? 'requestID local' : 'requestID web';
	echo $c->headerOver($n . $n . 'New name:  ') . $c->primary($title) .
	$c->headerOver('Old name:  ') . $c->primary($pieces[1]) .
	$c->headerOver('New cat:   ') . $c->primary($newcatname) .
	$c->headerOver('Group:     ') . $c->primary(trim($pieces[2])) .
	$c->headerOver('Method:    ') . $c->primary($method) .
	$c->headerOver('ReleaseID: ') . $c->primary($pieces[0]);
	$updated++;
} else {
	$db->queryExec('UPDATE releases SET reqidstatus = -3 WHERE id = ' . $pieces[0]);
	echo '.';
}

function getReleaseNameFromRequestID($site, $requestID, $groupName)
{
	$s = new Sites();
	$site = $s->get();
	if ($site->request_url == '') {
		return false;
	}
	// Build Request URL
	$req_url1 = str_ireplace('[GROUP_NM]', urlencode($groupName), $site->request_url);
	$req_url = str_ireplace('[REQUEST_ID]', urlencode($requestID), $req_url1);
	$xml = simplexml_load_file($req_url);
	if (($xml == false) || (count($xml) == 0)) {
		return false;
	}
	$request = $xml->request[0];
	if (isset($request)) {
		return array('title' => $request['name'], 'id' => 'NULL');
	}
}

function localLookup($requestID, $groupName, $oldname)
{
	$db = new DB();
	$groups = new Groups();
	$groupid = $groups->getIDByName($groupName);
	$run = $db->queryOneRow(sprintf("SELECT id, title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
	if (isset($run['title']) && preg_match('/s\d+/i', $run['title']) && !preg_match('/s\d+e\d+/i', $run['title'])) {
		return false;
	}
	if (isset($run['title'])) {
		return array('title' => $run['title'], 'id' => $run['id']);
	}
	if (preg_match('/\[#?a\.b\.teevee\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.teevee');
	} else if (preg_match('/\[#?a\.b\.moovee\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.moovee');
	} else if (preg_match('/\[#?a\.b\.erotica\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.erotica');
	} else if (preg_match('/\[#?a\.b\.foreign\]/', $oldname)) {
		$groupid = $groups->getIDByName('alt.binaries.mom');
	} else if ($groupName == 'alt.binaries.etc') {
		$groupid = $groups->getIDByName('alt.binaries.teevee');
	}
	$run1 = $db->queryOneRow(sprintf("SELECT id, title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
	if (isset($run1['title'])) {
		return array('title' => $run['title'], 'id' => $run['id']);
	}
}
