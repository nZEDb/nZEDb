<?php
require_once dirname(__FILE__) . '/config.php';
require_once nZEDb_LIB . 'backfill.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'page.php';
require_once nZEDb_LIB . 'category.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
if (!isset($argv[1]) || ( $argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1])))
	exit($c->error("\nThis script tries to match an MD5 of the releases.name or releases.searchname to predb.md5 doing local lookup only.\n"
		."php requestid.php 1000		...: to limit to 1000 sorted by newest postdate.\n"
		."php requestid.php full 		...: to run on full database.\n"
		."php requestid.php all 		...: to run on all hashed releases(including previously renamed).\n"));

$db = new DB();
$page = new Page();
$n = "\n";
$category = new Category();
$groups = new Groups();
$consoletools = new ConsoleTools();
$timestart = TIME();
$counter = 0;

if (isset($argv[1]) && $argv[1] === "all")
	$qry = $db->queryDirect("SELECT r.id, r.name, r.categoryid, g.name AS groupname FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE (bitwise & 1280) = 1280");
else if (isset($argv[1]) && $argv[1] === "full")
	$qry = $db->queryDirect("SELECT r.id, r.name, r.categoryid, g.name AS groupname FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE (bitwise & 1284) = 1280 AND reqidstatus in (0, -1)");
else if (isset($argv[1]) && is_numeric($argv[1]))
	$qry = $db->queryDirect("SELECT r.id, r.name, r.categoryid, g.name AS groupname FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE (bitwise & 1280) = 1280 AND reqidstatus in (0, -1) ORDER BY postdate DESC LIMIT " . $argv[1]);

$total = $qry->rowCount();
if($total > 0)
{
	$precount = $db->queryOneRow('SELECT COUNT(*) AS count FROM predb WHERE requestid > 0');
	echo $c->header("\nComparing ".number_format($total).' releases against '.number_format($precount['count'])." Local requestID's.");
	sleep(2);
	
	foreach ($qry as $row)
	{
		if (!preg_match('/^\[\d+\]/', $row['name']) && !preg_match('/^\[ \d+ \]/', $row['name']))
		{
			$db->queryExec('UPDATE releases SET reqidstatus = -2 WHERE id = ' . $row['id']);
			exit('-');
		}

		$requestIDtmp = explode(']', substr($row['name'], 1));
		$bFound = false;
		$newTitle = '';
		
		if (count($requestIDtmp) >= 1)
		{
			$requestID = (int) trim($requestIDtmp[0]);
			if ($requestID != 0 and $requestID != '')
			{
				// Do a local lookup first
				$newTitle = localLookup($requestID, $row['groupname'], $row['name']);
				if ($newTitle != false && $newTitle != '')
					$bFound = true;
			}
		}

		if ($bFound === true)
		{
			$groupname = $groups->getByNameByID($row['groupname']);
			$determinedcat = $category->determineCategory($newTitle, $groupname);
			$run = $db->prepare(sprintf('UPDATE releases set reqidstatus = 1, bitwise = ((bitwise & ~4)|4), searchname = %s, categoryid = %d where id = %d', $db->escapeString($newTitle), $determinedcat, $row['id']));
			$run->execute();
			$newcatname = $category->getNameByID($determinedcat);
			$oldcatname = $category->getNameByID($row['categoryid']);

			echo 	$c->headerOver($n.$n.'New name:  ').$c->primary($newTitle).
					$c->headerOver('Old name:  ').$c->primary($row['name']).
					$c->headerOver('New cat:   ').$c->primary($newcatname).
					$c->headerOver('Old cat:   ').$c->primary($oldcatname).
					$c->headerOver('Group:     ').$c->primary($row['groupname']).
					$c->headerOver('Method:    ').$c->primary('requestID local').
					$c->headerOver('ReleaseID: ').$c->primary($row['id']);
			$counter++;
		}
		else
		{
			$db->queryExec('UPDATE releases SET reqidstatus = -3 WHERE id = ' . $row['id']);
			echo ".";
		}
	}
	if ($total > 0)
		echo $c->header("\nRenamed ".$counter." releases in ".$consoletools->convertTime(TIME() - $timestart).".");
	else
		echo $c->info("\nNothing to do.");
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
