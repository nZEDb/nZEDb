<?php
require_once(dirname(__FILE__) . '/../../../www/config.php');

use nzedb\db\Settings;

/* This script will allow you to move from single collections/binaries/parts tables to TPG without having to run reset_truncate.
  Please STOP all update scripts before running this script.

  Use the following options to run:
  php convert_to_tpg.php true               Convert c/b/p to tpg leaving current collections/binaries/parts tables in-tact.
  php convert_to_tgp.php true delete        Convert c/b/p to tpg and TRUNCATE current collections/binaries/parts tables.
 */
$debug = false;
$pdo = new Settings();
$groups = new \Groups(['Settings' => $pdo]);
$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
$DoPartRepair = ($pdo->getSetting('partrepair') == '0') ? false : true;

if ((!isset($argv[1])) || $argv[1] != 'true') {
	exit($pdo->log->error("\nMandatory argument missing\n\n"
			. "This script will allow you to move from single collections/binaries/parts tables to TPG without having to run reset_truncate.\n"
			. "Please STOP all update scripts before running this script.\n\n"
			. "Use the following options to run:\n"
			. "php $argv[0] true             ...: Convert c/b/p to tpg leaving current collections/binaries/parts tables in-tact.\n"
			. "php $argv[0] true delete      ...: Convert c/b/p to tpg and TRUNCATE current collections/binaries/parts tables.\n"));
}

$clen = $pdo->queryOneRow('SELECT COUNT(*) AS total FROM collections;');
$cdone = 0;
$ccount = 1;
$gdone = 1;
$actgroups = $groups->getActive();
$glen = count($actgroups);
$newtables = $glen * 3;
$begintime = time();

echo "Creating new collections, binaries, and parts tables for each active group...\n";

foreach ($actgroups as $group) {
	if ($groups->createNewTPGTables($group['id']) === false) {
		exit($pdo->log->error("There is a problem creating new parts/files tables for group ${group['name']}."));
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
	$collections = $pdo->queryAssoc('select * from collections limit ' . $cdone . ',1000;');

	if ($collections instanceof \Traversable) {
		foreach ($collections as $collection) {
			$collection['subject'] = $pdo->escapeString($collection['subject']);
			$collection['fromname'] = $pdo->escapeString($collection['fromname']);
			$collection['date'] = $pdo->escapeString($collection['date']);
			$collection['collectionhash'] = $pdo->escapeString($collection['collectionhash']);
			$collection['dateadded'] = $pdo->escapeString($collection['dateadded']);
			$collection['xref'] = $pdo->escapeString($collection['xref']);
			$collection['releaseid'] = $pdo->escapeString($collection['releaseid']);
			$oldcid = array_shift($collection);
			if ($debug) {
				echo "\n\nCollection insert:\n";
				print_r($collection);
				echo sprintf("\nINSERT INTO collections_%d (subject, fromname, date, xref, totalfiles, group_id, collectionhash, dateadded, filecheck, filesize, releaseid) VALUES (%s)\n\n", $collection['group_id'], implode(', ', $collection));
			}
			$newcid = array('collectionid' => $pdo->queryInsert(sprintf('INSERT INTO collections_%d (subject, fromname, date, xref, totalfiles, group_id, collectionhash, dateadded, filecheck, filesize, releaseid) VALUES (%s);', $collection['group_id'], implode(', ', $collection))));
			$consoletools->overWrite('Collections Completed: ' . $consoletools->percentString($ccount, $clen['total']));

			//Get binaries and split to correct group tables.
			$binaries = $pdo->queryAssoc('SELECT * FROM binaries WHERE collectionID = ' . $oldcid . ';');

			if ($binaries instanceof \Traversable) {
				foreach ($binaries as $binary) {
					$binary['name'] = $pdo->escapeString($binary['name']);
					$binary['binaryhash'] = $pdo->escapeString($binary['binaryhash']);
					$oldbid = array_shift($binary);
					$binarynew = array_replace($binary, $newcid);
					if ($debug) {
						echo "\n\nBinary insert:\n";
						print_r($binarynew);
						echo sprintf("\nINSERT INTO binaries_%d (name, collectionid, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize) VALUES (%s)\n\n", $collection['group_id'], implode(', ', $binarynew));
					}
					$newbid = array('binaryid' => $pdo->queryInsert(sprintf('INSERT INTO binaries_%d (name, collectionid, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize) VALUES (%s);', $collection['group_id'], implode(', ', $binarynew))));

					//Get parts and split to correct group tables.
					$parts = $pdo->queryAssoc('SELECT * FROM parts WHERE binaryID = ' . $oldbid . ';');
					if ($parts instanceof \Traversable) {
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
							echo sprintf("\nINSERT INTO parts_%d (binaryid, messageid, number, partnumber, size, collection_id) VALUES %s;\n\n", $collection['group_id'], $partsnew);
						}
						$sql = sprintf('INSERT INTO parts_%d (binaryid, messageid, number, partnumber, size, collection_id) VALUES %s;', $collection['group_id'], $partsnew);
						$pdo->queryExec($sql);
					}
				}
			}
			$ccount++;
		}
	}
	$cdone += 1000;
}

if ($DoPartRepair === true) {
	foreach ($actgroups as $group) {
		$pcount = 1;
		$pdone = 0;
		$sql = sprintf('SELECT COUNT(*) AS total FROM partrepair where group_id = %d;', $group['id']);
		$plen = $pdo->queryOneRow($sql);
		while ($pdone < $plen['total']) {
			// Only load 10000 partrepair records per loop to not overload memory.
			$partrepairs = $pdo->queryAssoc(sprintf('select * from partrepair where group_id = %d limit %d, 10000;', $group['id'], $pdone));
			if ($partrepairs instanceof \Traversable) {
				foreach ($partrepairs as $partrepair) {
					$partrepair['numberid'] = $pdo->escapeString($partrepair['numberid']);
					$partrepair['group_id'] = $pdo->escapeString($partrepair['group_id']);
					$partrepair['attempts'] = $pdo->escapeString($partrepair['attempts']);
					if ($debug) {
						echo "\n\nPart Repair insert:\n";
						print_r($partrepair);
						echo sprintf("\nINSERT INTO partrepair_%d (numberid, group_id, attempts) VALUES (%s, %s, %s)\n\n", $group['id'], $partrepair['numberid'], $partrepair['group_id'], $partrepair['attempts']);
					}
					$pdo->queryExec(sprintf('INSERT INTO partrepair_%d (numberid, group_id, attempts) VALUES (%s, %s, %s);', $group['id'], $partrepair['numberid'], $partrepair['group_id'], $partrepair['attempts']));
					$consoletools->overWrite('Part Repairs Completed for ' . $group['name'] . ':' . $consoletools->percentString($pcount, $plen['total']));
					$pcount++;
				}
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
	$pdo->queryDirect('TRUNCATE TABLE collections;');
	$pdo->queryDirect('TRUNCATE TABLE binaries;');
	$pdo->queryDirect('TRUNCATE TABLE parts');
	$pdo->queryDirect('TRUNCATE TABLE partrepair');
	echo "Complete.\n";
}
// Update TPG setting in site-edit.
$pdo->queryExec('UPDATE settings SET value = 1 where setting = \'tablepergroup\';');
$pdo->queryExec('UPDATE tmux SET value = 2 where setting = \'releases\';');
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
