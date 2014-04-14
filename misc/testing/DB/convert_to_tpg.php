<?php
require_once(dirname(__FILE__) . '/../../../www/config.php');

use nzedb\db\DB;

/* This script will allow you to move from single collections/binaries/parts tables to TPG without having to run reset_truncate.
  Please STOP all update scripts before running this script.

  Use the following options to run:
  php convert_to_tpg.php true               Convert c/b/p to tpg leaving current collections/binaries/parts tables in-tact.
  php convert_to_tgp.php true delete        Convert c/b/p to tpg and TRUNCATE current collections/binaries/parts tables.
 */
$debug = false;
$db = new DB();
$c = new ColorCLI();
$groups = new Groups();
$consoletools = new ConsoleTools();
$s = new Sites();
$site = $s->get();
$DoPartRepair = ($site->partrepair == '0') ? false : true;

if ((!isset($argv[1])) || $argv[1] != 'true') {
	exit($c->error("\mMandatory argument missing\n\n"
			. "This script will allow you to move from single collections/binaries/parts tables to TPG without having to run reset_truncate.\n"
			. "Please STOP all update scripts before running this script.\n\n"
			. "Use the following options to run:\n"
			. "php $argv[0] true             ...: Convert c/b/p to tpg leaving current collections/binaries/parts tables in-tact.\n"
			. "php $argv[0] true delete      ...: Convert c/b/p to tpg and TRUNCATE current collections/binaries/parts tables.\n"));
}

$clen = $db->queryOneRow('SELECT COUNT(*) AS total FROM collections;');
$cdone = 0;
$ccount = 1;
$gdone = 1;
$actgroups = $groups->getActive();
$glen = count($actgroups);
$newtables = $glen * 3;
$begintime = time();

echo "Creating new collections, binaries, and parts tables for each active group...\n";

foreach ($actgroups as $group) {
	if ($db->newtables($group['id']) === false) {
		exit($c->error("There is a problem creating new parts/files tables for group ${group['name']}."));
	}
	$consoletools->overWrite("Tables Created: " . $consoletools->percentString($gdone * 3, $newtables));
	$gdone++;
}
$endtime = time();
echo "\nTable creation took " . $consoletools->convertTime($endtime - $begintime) . ".\n";
$starttime = time();
echo "\nNew tables created, moving data from old tables to new tables.\nThis will take awhile....\n\n";
while ($cdone < $clen['total']) {
	// Only load 1000 collections per loop to not overload memory.
	$collections = $db->queryAssoc('select * from collections limit ' . $cdone . ',1000;');
	foreach ($collections as $collection) {
		/* foreach ($collection as $ckey => $cval)
		  {
		  //if (is_int($ckey))
		  //unset($collection[$ckey]);
		  if ($ckey != 'groupid')
		  $collection[$ckey] = $db->escapeString($cval);
		  } */
		$collection['subject'] = $db->escapeString($collection['subject']);
		$collection['fromname'] = $db->escapeString($collection['fromname']);
		$collection['date'] = $db->escapeString($collection['date']);
		$collection['collectionhash'] = $db->escapeString($collection['collectionhash']);
		$collection['dateadded'] = $db->escapeString($collection['dateadded']);
		$collection['xref'] = $db->escapeString($collection['xref']);
		$collection['releaseid'] = $db->escapeString($collection['releaseid']);
		$oldcid = array_shift($collection);
		if ($debug) {
			echo "\n\nCollection insert:\n";
			print_r($collection);
			echo sprintf("\nINSERT INTO collections_%d (subject, fromname, date, xref, totalfiles, groupid, collectionhash, dateadded, filecheck, filesize, releaseid) VALUES (%s)\n\n", $collection['groupid'], implode(', ', $collection));
		}
		$newcid = array('collectionid' => $db->queryInsert(sprintf('INSERT INTO collections_%d (subject, fromname, date, xref, totalfiles, groupid, collectionhash, dateadded, filecheck, filesize, releaseid) VALUES (%s);', $collection['groupid'], implode(', ', $collection))));
		$consoletools->overWrite('Collections Completed: ' . $consoletools->percentString($ccount, $clen['total']));

		//Get binaries and split to correct group tables.
		$binaries = $db->queryAssoc('SELECT * FROM binaries WHERE collectionID = ' . $oldcid . ';');
		foreach ($binaries as $binary) {
			$binary['name'] = $db->escapeString($binary['name']);
			$binary['binaryhash'] = $db->escapeString($binary['binaryhash']);
			$oldbid = array_shift($binary);
			$binarynew = array_replace($binary, $newcid);
			if ($debug) {
				echo "\n\nBinary insert:\n";
				print_r($binarynew);
				echo sprintf("\nINSERT INTO binaries_%d (name, collectionid, filenumber, totalparts, binaryhash, partcheck, partsize) VALUES (%s)\n\n", $collection['groupid'], implode(', ', $binarynew));
			}
			$newbid = array('binaryid' => $db->queryInsert(sprintf('INSERT INTO binaries_%d (name, collectionid, filenumber, totalparts, binaryhash, partcheck, partsize) VALUES (%s);', $collection['groupid'], implode(', ', $binarynew))));


			//Get parts and split to correct group tables.
			$parts = $db->queryAssoc('SELECT * FROM parts WHERE binaryID = ' . $oldbid . ';');
			$firstpart = true;
			$partsnew = '';
			foreach ($parts as $part) {
				$oldpid = array_shift($part);
				$partnew = array_replace($part, $newbid);

				$partsnew .= '(\'' . implode('\', \'', $partnew) . '\'), ';
			}
			$partsnew = substr($partsnew, 0, -2);
			if ($debug) {
				echo "\n\nParts insert:\n";
				echo sprintf("\nINSERT INTO parts_%d (binaryid, messageid, number, partnumber, size) VALUES %s;\n\n", $collection['groupid'], $partsnew);
			}
			$sql = sprintf('INSERT INTO parts_%d (binaryid, messageid, number, partnumber, size) VALUES %s;', $collection['groupid'], $partsnew);
			$db->queryExec($sql);
		}
		$ccount++;
	}
	$cdone += 1000;
}

if ($DoPartRepair === true) {
	foreach ($actgroups as $group) {
		$pcount = 1;
		$pdone = 0;
		$sql = sprintf('SELECT COUNT(*) AS total FROM partrepair where groupid = %d;', $group['id']);
		$plen = $db->queryOneRow($sql);
		while ($pdone < $plen['total']) {
			// Only load 10000 partrepair records per loop to not overload memory.
			$partrepairs = $db->queryAssoc(sprintf('select * from partrepair where groupid = %d limit %d, 10000;', $group['id'], $pdone));
			foreach ($partrepairs as $partrepair) {
				$partrepair['numberid'] = $db->escapeString($partrepair['numberid']);
				$partrepair['groupid'] = $db->escapeString($partrepair['groupid']);
				$partrepair['attempts'] = $db->escapeString($partrepair['attempts']);
				if ($debug) {
					echo "\n\nPart Repair insert:\n";
					print_r($partrepair);
					echo sprintf("\nINSERT INTO partrepair_%d (numberid, groupid, attempts) VALUES (%s, %s, %s)\n\n", $group['id'], $partrepair['numberid'], $partrepair['groupid'], $partrepair['attempts']);
				}
				$db->queryExec(sprintf('INSERT INTO partrepair_%d (numberid, groupid, attempts) VALUES (%s, %s, %s);', $group['id'], $partrepair['numberid'], $partrepair['groupid'], $partrepair['attempts']));
				$consoletools->overWrite('Part Repairs Completed for ' . $group['name'] . ':' . $consoletools->percentString($pcount, $plen['total']));
				$pcount++;
			}
			$pdone += 10000;
		}
	}
}

$endtime = time();
echo "\nTable population took " . $consoletools->convertTimer($endtime - $starttime) . ".\n";

//Truncate old tables to save space.
if (isset($argv[2]) && $argv[2] == 'delete') {
	echo "Truncating old tables...\n";
	$db->queryDirect('TRUNCATE TABLE collections;');
	$db->queryDirect('TRUNCATE TABLE binaries;');
	$db->queryDirect('TRUNCATE TABLE parts');
	$db->queryDirect('TRUNCATE TABLE partrepair');
	echo "Complete.\n";
}
// Update TPG setting in site-edit.
$db->queryExec('UPDATE site SET value = 1 where setting = \'tablepergroup\';');
$db->queryExec('UPDATE tmux SET value = 2 where setting = \'releases\';');
echo "New tables have been created.\nTable Per Group has been set to  to \"TRUE\" in site-edit.\nUpdate Releases has been set to Threaded in tmux-edit.\n";

function multi_implode($array, $glue)
{
	$ret = '';

	foreach ($array as $item) {
		if (is_array($item)) {
			$ret .= '(' . multi_implode($item, $glue) . '), ';
		} else {
			$ret .= $item . $glue;
		}
	}

	$ret = substr($ret, 0, 0 - strlen($glue));

	return $ret;
}
