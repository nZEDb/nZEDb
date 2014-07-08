<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from requestid_threaded.py."));
}
$pieces = explode('                       ', $argv[1]);
$web = $pieces[3];

$pdo = new Settings();
$n = "\n";
$category = new Categorize();
$groups = new Groups();
$requestID = 0;
if (preg_match('/^\[ ?(\d{4,6}) ?\]/', $pieces[1], $match) ||
	preg_match('/^REQ\s*(\d{4,6})/i', $pieces[1], $match) ||
	preg_match('/^(\d{4,6})-\d{1}\[/', $pieces[1], $match) ||
	preg_match('/(\d{4,6}) -/', $pieces[1], $match)
) {
	$requestID = (int)$match[1];
} else {
	$pdo->queryExec('UPDATE releases SET reqidstatus = -2 WHERE id = ' . $pieces[0]);
	exit('.');
}
$bFound = false;
$newTitle = '';
$updated = 0;

if ($requestID != 0 and $requestID != '') {
	// Do a local lookup first
	$newTitle = localLookup($requestID, $pieces[2], $pieces[1]);
	if (is_array($newTitle) && $newTitle['title'] != '') {
		$bFound = true;
		$local = true;
	} else if ($web == "True") {
		$newTitle = getReleaseNameFromRequestID($site, $requestID, $pieces[2]);
		if (is_array($newTitle) && $newTitle['title'] != '') {
			$bFound = true;
			$local = false;
		}
	}
}
if ($bFound === true) {
	$title = $newTitle['title'];
	$preid = $newTitle['id'];
	$groupname = $groups->getByNameByID($pieces[2]);
	$groupid = $groups->getIDByName($pieces[2]);
	$determinedcat = $category->determineCategory($title, $groupid);
	if ($groupid !== 0) {
		$dupe = $pdo->queryOneRow(sprintf('SELECT requestid FROM predb WHERE title = %s', $pdo->escapeString($title)));
		if ($dupe === false || ($dupe !== false && $dupe['requestid'] != $requestID)) {
			$preid = $pdo->queryInsert(
				sprintf("
				INSERT INTO predb (title, source, requestid, group_id)
				VALUES (%s, %s, %d, %d)",
					$pdo->escapeString($title),
					$pdo->escapeString('requestWEB'),
					$requestID, $groupid
				)
			);
		}
	} else if ($groupid === 0) {
		echo $requestID . "\n";
	}
	$run = $pdo->queryDirect(sprintf("UPDATE releases set rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
			. "preid = %d, reqidstatus = 1, isrenamed = 1, searchname = %s, categoryid = %d where id = %d", $preid, $pdo->escapeString($title), $determinedcat, $pieces[0]));

	$newcatname = $category->getNameByID($determinedcat);
	$method = ($local === true) ? 'requestID local' : 'requestID web';

	NameFixer::echoChangedReleaseName(array(
			'new_name'     => $title,
			'old_name'     => $pieces[1],
			'new_category' => $newcatname,
			'old_category' => '',
			'group'        => trim($pieces[2]),
			'release_id'   => $pieces[0],
			'method'       => $method
		)
	);
	$updated++;
} else {
	$pdo->queryExec('UPDATE releases SET reqidstatus = -3 WHERE id = ' . $pieces[0]);
	echo '.';
}

function getReleaseNameFromRequestID($site, $requestID, $groupName)
{
	global $pdo;
	$requestURL = $pdo->getSetting('request_url');
	if ($requestURL == '') {
		return false;
	}
	// Build Request URL
	$req_url1 = str_ireplace('[GROUP_NM]', urlencode($groupName), $requestURL);
	$req_url = str_ireplace('[REQUEST_ID]', urlencode($requestID), $req_url1);
	$xml = @simplexml_load_file($req_url);
	if (($xml == false) || (count($xml) == 0)) {
		return false;
	}
	$request = $xml->request[0];
	if (isset($request) && !empty($request['name'])) {
		return array('title' => $request['name'], 'id' => 'NULL');
	}
}

function localLookup($requestID, $groupName, $oldname)
{
	global $pdo;
	$groups = new Groups();
	$groupid = $groups->getIDByName($groupName);
	$run = $pdo->queryOneRow(sprintf("SELECT id, title FROM predb WHERE requestid = %d AND group_id = %d", $requestID, $groupid));
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
	$run1 = $pdo->queryOneRow(sprintf("SELECT id, title FROM predb WHERE requestid = %d AND group_id = %d", $requestID, $groupid));
	if (isset($run1['title'])) {
		return array('title' => $run['title'], 'id' => $run['id']);
	}
}
