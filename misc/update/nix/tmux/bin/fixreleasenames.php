<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;
use \nzedb\processing\PostProcess;

$pdo = new Settings();

if (!isset($argv[1])) {
	exit($pdo->log->error("This script is not intended to be run manually, it is called from fixreleasenames_threaded.py."));
} else if (isset($argv[1])) {
	$namefixer = new \NameFixer(['Settings' => $pdo]);
	$pieces = explode(' ', $argv[1]);
	if (isset($pieces[1]) && $pieces[0] == 'nfo') {
		$release = $pieces[1];
		if ($res = $pdo->queryOneRow(sprintf('SELECT rel.guid AS guid, nfo.releaseid AS nfoid, rel.group_id, rel.categoryid, rel.name, rel.searchname, uncompress(nfo) AS textstring, rel.id AS releaseid FROM releases rel INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) WHERE rel.id = %d', $release))) {
			//ignore encrypted nfos
			if (preg_match('/^=newz\[NZB\]=\w+/', $res['textstring'])) {
				$namefixer->done = $namefixer->matched = false;
				$pdo->queryDirect(sprintf('UPDATE releases SET proc_nfo = 1 WHERE id = %d', $res['releaseid']));
				$namefixer->checked++;
				echo '.';
			} else {
				$namefixer->done = $namefixer->matched = false;
				if ($namefixer->checkName($res, true, 'NFO, ', 1, 1) !== true) {
					echo '.';
				}
				$namefixer->checked++;
			}
		}
	} else if (isset($pieces[1]) && $pieces[0] == 'filename') {
		$release = $pieces[1];
		if ($res = $pdo->queryOneRow(sprintf('SELECT relfiles.name AS textstring, rel.categoryid, rel.searchname, '
				. 'rel.group_id, relfiles.releaseid AS fileid, rel.id AS releaseid, rel.name FROM releases rel '
				. 'INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) WHERE rel.id = %d', $release))) {
			$namefixer->done = $namefixer->matched = false;
			if ($namefixer->checkName($res, true, 'Filenames, ', 1, 1) !== true) {
				echo '.';
			}
			$namefixer->checked++;
		}
	} else if (isset($pieces[1]) && $pieces[0] == 'md5') {
		$release = $pieces[1];
		if ($res = $pdo->queryOneRow(sprintf('SELECT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.group_id, dehashstatus, rf.name AS filename '
							. 'FROM releases r LEFT JOIN releasefiles rf ON r.id = rf.releaseid WHERE r.id = %d', $release))) {
			if (preg_match('/[a-fA-F0-9]{32,40}/i', $res['name'], $matches)) {
				$namefixer->matchPredbHash($matches[0], $res, 1, 1, true, 1);
			} else if (preg_match('/[a-fA-F0-9]{32,40}/i', $res['filename'], $matches)) {
				$namefixer->matchPredbHash($matches[0], $res, 1, 1, true, 1);
			} else {
				$pdo->queryExec(sprintf("UPDATE releases SET dehashstatus = %d - 1 WHERE id = %d", $res['dehashstatus'], $res['releaseid']));
				echo '.';
			}
		}
	} else if (isset($pieces[1]) && $pieces[0] == 'par2') {
		//echo PHP_EOL . microtime();
		$nntp = new \NNTP(['Settings' => $pdo]);
		if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($pdo->log->error("Unable to connect to usenet."));
		}

		$relID = $pieces[1];
		$guid = $pieces[2];
		$groupID = $pieces[3];
		$nzbcontents = new \NZBContents(
			array(
				'Echo' => true, 'NNTP' => $nntp, 'Settings' => $pdo,
				'Nfo' => new \Nfo(['Settings' => $pdo, 'Echo' => true]),
				'PostProcess' => new \PostProcess(['Settings' => $pdo, 'NameFixer' => $namefixer])
			)
		);
		//echo " " . microtime();
		$res = $nzbcontents->checkPAR2($guid, $relID, $groupID, 1, 1);
		//echo " " . microtime() . " ";
		if ($res === false) {
			echo '.';
		}

	} else if (isset($pieces[1]) && $pieces[0] == 'miscsorter') {
		$nntp = new \NNTP(['Settings' => $pdo]);
		if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($pdo->log->error("Unable to connect to usenet."));
		}

		$sorter = new \MiscSorter(true);
		$relID = $pieces[1];
		$res = $sorter->nfosorter(null, $relID, $nntp);
		if ($res != true) {
			$pdo->queryExec(sprintf('UPDATE releases SET proc_sorter = 1 WHERE id = %d', $relID));
			echo '.';
		}

	} else if (isset($pieces[1]) && $pieces[0] == 'predbft') {
		$pre = $pieces[1];
		if ($res = $pdo->queryOneRow(sprintf('SELECT id AS preid, title, source, searched FROM predb '
							. 'WHERE id = %d', $pre))) {
			$namefixer->done = $namefixer->matched = false;
			$ftmatched = $searched = 0;
			$ftmatched = $namefixer->matchPredbFT($res, 1, 1, true, 1);
			if ($ftmatched > 0) {
				$searched = 1;
			} elseif ($ftmatched < 0) {
				$searched = -6;
				echo "*";
			} else {
				$searched = $res['searched'] - 1;
				echo ".";
			}
			$pdo->queryExec(sprintf("UPDATE predb SET searched = %d WHERE id = %d", $searched, $res['preid']));
			$namefixer->checked++;
		}

	}
}
