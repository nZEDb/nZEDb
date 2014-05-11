<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\DB;

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from fixreleasenames_threaded.py."));
} else if (isset($argv[1])) {
	$db = new DB();
	$namefixer = new NameFixer(true);
	$pieces = explode(' ', $argv[1]);
	if (isset($pieces[1]) && $pieces[0] == 'nfo') {
		$release = $pieces[1];
		if ($res = $db->queryOneRow(sprintf('SELECT rel.guid AS guid, nfo.releaseid AS nfoid, rel.groupid, rel.categoryid, rel.searchname, uncompress(nfo) AS textstring, rel.id AS releaseid FROM releases rel INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) WHERE rel.id = %d', $release))) {
			//ignore encrypted nfos
			if (preg_match('/^=newz\[NZB\]=\w+/', $res['textstring'])) {
				$namefixer->done = $namefixer->matched = false;
				$db->queryDirect(sprintf('UPDATE releases SET proc_nfo = 1 WHERE id = %d', $res['releaseid']));
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
		if ($res = $db->queryOneRow(sprintf('SELECT relfiles.name AS textstring, rel.categoryid, rel.searchname, '
				. 'rel.groupid, relfiles.releaseid AS fileid, rel.id AS releaseid FROM releases rel '
				. 'INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) WHERE rel.id = %d', $release))) {
			$namefixer->done = $namefixer->matched = false;
			if ($namefixer->checkName($res, true, 'Filenames, ', 1, 1) !== true) {
				echo '.';
			}
			$namefixer->checked++;
		}
	} else if (isset($pieces[1]) && $pieces[0] == 'md5') {
		$release = $pieces[1];
		if ($res = $db->queryOneRow(sprintf('SELECT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.groupid, dehashstatus, rf.name AS filename FROM releases r LEFT JOIN releasefiles rf ON r.id = rf.releaseid WHERE r.id = %d', $release))) {
			if (preg_match('/[a-fA-F0-9]{32,40}/i', $res['name'], $matches)) {
				$namefixer->matchPredbHash($matches[0], $res, 1, 1, true, 1);
			} else if (preg_match('/[a-fA-F0-9]{32,40}/i', $res['filename'], $matches)) {
				$namefixer->matchPredbHash($matches[0], $res, 1, 1, true, 1);
			} else {
				$db->queryExec(sprintf("UPDATE releases SET dehashstatus = %d - 1 WHERE id = %d", $res['dehashstatus'], $res['releaseid']));
				echo '.';
			}
		}
	} else if (isset($pieces[1]) && $pieces[0] == 'par2') {
		$s = new Sites();
		$site = $s->get();
		$nntp = new NNTP();
		if (($site->alternate_nntp === '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($c->error("Unable to connect to usenet."));
		}
		if ($site->nntpproxy === "1") {
			usleep(500000);
		}

		$relID = $pieces[1];
		$guid = $pieces[2];
		$groupID = $pieces[3];
		$nzbcontents = new NZBContents(array('echo' => true, 'nntp' => $nntp, 'nfo' => new Nfo(), 'db' => $db, 'pp' => new PostProcess(true)));
		$res = $nzbcontents->checkPAR2($guid, $relID, $groupID, 1, 1);
		if ($res === false) {
			echo '.';
		}
		if ($site->nntpproxy != "1") {
			$nntp->doQuit();
		}
	} else if (isset($pieces[1]) && $pieces[0] == 'miscsorter') {
		$s = new Sites();
		$site = $s->get();
		$nntp = new NNTP();
		if (($site->alternate_nntp === '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($c->error("Unable to connect to usenet."));
		}
		if ($site->nntpproxy === "1") {
			usleep(500000);
		}

		$sorter = new MiscSorter(true);
		$relID = $pieces[1];
		$res = $sorter->nfosorter(null, $relID, $nntp);
		if ($res != true) {
			$db->queryExec(sprintf('UPDATE releases SET proc_sorter = 1 WHERE id = %d', $relID));
			echo '.';
		}
		if ($site->nntpproxy != "1") {
			$nntp->doQuit();
		}
	}
}
