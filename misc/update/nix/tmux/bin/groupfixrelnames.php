<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from groupfixrelnames_threaded.py."));
} else if (isset($argv[1])) {
	$pdo = new Settings();
	$namefixer = new NameFixer(true);
	$pieces = explode(' ', $argv[1]);
	$proxy = $pdo->getSetting('nntpproxy');
	$groupID = $pieces[1];
	$maxperrun = $pieces[2];

	if ($pieces[0] == 'nfo' && isset($pieces[1]) && isset($pieces[2]) && is_numeric($pieces[2])) {
		$releases = $pdo->queryDirect(
						sprintf('
							SELECT r.id AS releaseid, r.guid, r.group_id, r.categoryid, r.name, r.searchname,
								uncompress(nfo) AS textstring
							FROM releases r
							INNER JOIN releasenfo rn ON r.id = rn.releaseid
							WHERE r.nzbstatus = 1
							AND r.preid = 0
							AND r.group_id = %d
							ORDER BY r.postdate DESC
							LIMIT %s',
							$groupID,
							$maxperrun
						)
		);
		if ($releases !== false) {
			foreach ($releases as $release) {
				if (preg_match('/^=newz\[NZB\]=\w+/', $release['textstring'])) {
					$namefixer->done = $namefixer->matched = false;
					$pdo->queryDirect(sprintf('UPDATE releases SET proc_nfo = 1 WHERE id = %d', $release['releaseid']));
					$namefixer->checked++;
					echo '.';
				} else {
					$namefixer->done = $namefixer->matched = false;
					if ($namefixer->checkName($release, true, 'NFO, ', 1, 1) !== true) {
						echo '.';
					}
					$namefixer->checked++;
				}
			}
		}
	} else if ($pieces[0] == 'filename' && isset($pieces[1]) && isset($pieces[2]) && is_numeric($pieces[2])) {
		$releases = $pdo->queryDirect(
						sprintf('
							SELECT rf.name AS textstring, rf.releaseid AS fileid,
								r.id AS releaseid, r.name, r.searchname, r.categoryid, r.group_id
							FROM releases r
							INNER JOIN releasefiles rf ON r.id = rf.releaseid
							WHERE r.nzbstatus = 1 AND r.proc_files = 0
							AND r.preid = 0
							AND r.group_id = %d
							ORDER BY r.postdate ASC
							LIMIT %s',
							$groupID,
							$maxperrun
						)
		);
		if ($releases !== false) {
			foreach ($releases as $release) {
				$namefixer->done = $namefixer->matched = false;
				if ($namefixer->checkName($release, true, 'Filenames, ', 1, 1) !== true) {
					echo '.';
				}
				$namefixer->checked++;
			}
		}
	} else if ($pieces[0] == 'md5' && isset($pieces[1]) && isset($pieces[2]) && is_numeric($pieces[2])) {
		$releases = $pdo->queryDirect(
						sprintf('
							SELECT DISTINCT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.group_id, r.dehashstatus,
								rf.name AS filename
							FROM releases r
							LEFT OUTER JOIN releasefiles rf ON r.id = rf.releaseid AND rf.ishashed = 1
							WHERE nzbstatus = 1 AND r.dehashstatus BETWEEN -6 AND 0
							AND r.preid = 0
							AND r.group_id = %d
							ORDER BY r.dehashstatus DESC, r.postdate ASC
							LIMIT %s',
							$groupID,
							$maxperrun
						)
		);
		if ($releases !== false) {
			foreach ($releases as $release) {
				if (preg_match('/[a-fA-F0-9]{32,40}/i', $release['name'], $matches)) {
					$namefixer->matchPredbHash($matches[0], $release, 1, 1, true, 1);
				} else if (preg_match('/[a-fA-F0-9]{32,40}/i', $release['filename'], $matches)) {
					$namefixer->matchPredbHash($matches[0], $release, 1, 1, true, 1);
				} else {
					$pdo->queryExec(sprintf("UPDATE releases SET dehashstatus = %d - 1 WHERE id = %d", $release['dehashstatus'], $release['releaseid']));
					echo '.';
				}
			}
		}
	} else if ($pieces[0] == 'par2' && isset($pieces[1]) && isset($pieces[2]) && is_numeric($pieces[2])) {
		$nntp = new NNTP();
		if (($pdo->getSetting('alternate_nntp') == '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($c->error("Unable to connect to usenet."));
		}
		if ($proxy == "1") {
			usleep(500000);
		}

		$releases = $pdo->queryDirect(
						sprintf('
							SELECT r.id AS releaseid, r.guid
							FROM releases r
							WHERE r.nzbstatus = 1 AND r.proc_par2 = 0
							AND r.preid = 0
							AND r.group_id = %d
							ORDER BY r.postdate ASC
							LIMIT %s',
							$groupID,
							$maxperrun
						)
		);
		if ($releases !== false) {
			$nzbcontents = new NZBContents(array('echo' => true, 'nntp' => $nntp, 'nfo' => new Nfo(), 'db' => $pdo, 'pp' => new PostProcess(true)));
			foreach ($releases as $release) {
				$res = $nzbcontents->checkPAR2($release['guid'], $release['releaseid'], $groupID, 1, 1);
				if ($res === false) {
					echo '.';
				}
			}
		}
		if ($proxy != "1") {
			$nntp->doQuit();
		}
	} else if ($pieces[0] == 'miscsorter' && isset($pieces[1]) && isset($pieces[2]) && is_numeric($pieces[2])) {
		$nntp = new NNTP();
		if (($pdo->getSetting('alternate_nntp') == '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($c->error("Unable to connect to usenet."));
		}
		if ($proxy == "1") {
			usleep(500000);
		}

		$releases = $pdo->queryDirect(
						sprintf('
							SELECT r.id AS releaseid
							FROM releases r
							WHERE r.nzbstatus = 1 AND r.nfostatus = 1
							AND r.proc_sorter = 0 AND r.isrenamed = 0
							AND r.preid = 0
							AND r.group_id = %d
							ORDER BY r.postdate DESC
							LIMIT %s',
							$groupID,
							$maxperrun
						)
		);
		if ($releases !== false) {
			$sorter = new MiscSorter(true);
			foreach ($releases as $release) {
				$res = $sorter->nfosorter(null, $release['releaseid'], $nntp);
				if ($res != true) {
					$pdo->queryExec(sprintf('UPDATE releases SET proc_sorter = 1 WHERE id = %d', $release['releaseid']));
					echo '.';
				}
			}
		}
		if ($proxy != "1") {
			$nntp->doQuit();
		}
	}
}