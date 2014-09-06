<?php
require_once dirname(__FILE__) . '/../../../config.php';

use \nzedb\db\Settings;
use \nzedb\processing\PostProcess;

$pdo = new Settings();

if (!isset($argv[1])) {
	exit($pdo->log->error("This script is not intended to be run manually, it is called from groupfixrelnames_threaded.py."));
} else if (isset($argv[1])) {
	$namefixer = new \NameFixer(['Settings' => $pdo]);
	$pieces = explode(' ', $argv[1]);
	$guidChar = $pieces[1];
	$maxperrun = $pieces[2];
	$thread = $pieces[3];

	switch (true) {
		case $pieces[0] === 'nfo' && isset($guidChar) && isset($maxperrun) && is_numeric($maxperrun):
			$releases = $pdo->queryDirect(
							sprintf('
								SELECT r.id AS releaseid, r.guid, r.group_id, r.categoryid, r.name, r.searchname,
									uncompress(nfo) AS textstring
								FROM releases r
								INNER JOIN releasenfo rn ON r.id = rn.releaseid
								WHERE r.guid %s
								AND r.nzbstatus = 1
								AND r.proc_nfo = 0
								AND r.nfostatus = 1
								AND r.preid = 0
								ORDER BY r.postdate DESC
								LIMIT %s',
								$pdo->likeString($guidChar, false, true),
								$maxperrun
							)
			);

			if ($releases instanceof \Traversable) {
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
			break;
		case $pieces[0] === 'filename' && isset($guidChar) && isset($maxperrun) && is_numeric($maxperrun):
			$releases = $pdo->queryDirect(
							sprintf('
								SELECT rf.name AS textstring, rf.releaseid AS fileid,
									r.id AS releaseid, r.name, r.searchname, r.categoryid, r.group_id
								FROM releases r
								INNER JOIN releasefiles rf ON r.id = rf.releaseid
								WHERE r.guid %s
								AND r.nzbstatus = 1 AND r.proc_files = 0
								AND r.preid = 0
								ORDER BY r.postdate ASC
								LIMIT %s',
								$pdo->likeString($guidChar, false, true),
								$maxperrun
							)
			);

			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$namefixer->done = $namefixer->matched = false;
					if ($namefixer->checkName($release, true, 'Filenames, ', 1, 1) !== true) {
						echo '.';
					}
					$namefixer->checked++;
				}
			}
			break;
		case $pieces[0] === 'md5' && isset($guidChar) && isset($maxperrun) && is_numeric($maxperrun):
			$releases = $pdo->queryDirect(
							sprintf('
								SELECT DISTINCT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.group_id, r.dehashstatus,
									rf.name AS filename
								FROM releases r
								LEFT OUTER JOIN releasefiles rf ON r.id = rf.releaseid AND rf.ishashed = 1
								WHERE r.guid %s
								AND nzbstatus = 1 AND r.ishashed = 1
								AND r.dehashstatus BETWEEN -6 AND 0
								AND r.preid = 0
								ORDER BY r.dehashstatus DESC, r.postdate ASC
								LIMIT %s',
								$pdo->likeString($guidChar, false, true),
								$maxperrun
							)
			);

			if ($releases instanceof \Traversable) {
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
			break;
		case $pieces[0] === 'par2' && isset($guidChar) && isset($maxperrun) && is_numeric($maxperrun):
			$releases = $pdo->queryDirect(
							sprintf('
								SELECT r.id AS releaseid, r.guid, r.group_id
								FROM releases r
								WHERE r.guid %s
								AND r.nzbstatus = 1
								AND r.proc_par2 = 0
								AND r.preid = 0
								ORDER BY r.postdate ASC
								LIMIT %s',
								$pdo->likeString($guidChar, false, true),
								$maxperrun
							)
			);

			if ($releases instanceof \Traversable) {
				$nntp = new \NNTP(['Settings' => $pdo]);
				if (($pdo->getSetting('alternate_nntp') == '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
					exit($pdo->log->error("Unable to connect to usenet."));
				}

				$Nfo = new \Nfo(['Settings' => $pdo, 'Echo' => true]);
				$nzbcontents = new \NZBContents(
					array(
						'Echo' => true, 'NNTP' => $nntp, 'Nfo' => $Nfo, 'Settings' => $pdo,
						'PostProcess' => new PostProcess(['Settings' => $pdo, 'Nfo' => $Nfo, 'NameFixer' => $namefixer])
					)
				);
				foreach ($releases as $release) {
					$res = $nzbcontents->checkPAR2($release['guid'], $release['releaseid'], $release['group_id'], 1, 1);
					if ($res === false) {
						echo '.';
					}
				}
			}
			break;
		case $pieces[0] === 'miscsorter' && isset($guidChar) && isset($maxperrun) && is_numeric($maxperrun):
			$releases = $pdo->queryDirect(
							sprintf('
								SELECT r.id AS releaseid
								FROM releases r
								WHERE r.guid %s
								AND r.nzbstatus = 1 AND r.nfostatus = 1
								AND r.proc_sorter = 0 AND r.isrenamed = 0
								AND r.preid = 0
								ORDER BY r.postdate DESC
								LIMIT %s',
								$pdo->likeString($guidChar, false, true),
								$maxperrun
							)
			);

			if ($releases instanceof \Traversable) {
				$sorter = new \MiscSorter(true, $pdo);
				foreach ($releases as $release) {
					$res = $sorter->nfosorter(null, $release['releaseid']);
				}
			}
			break;
		case $pieces[0] === 'predbft' && isset($maxperrun) && is_numeric($maxperrun) && isset($thread) && is_numeric($thread):
			$pres = $pdo->queryDirect(
						sprintf('
							SELECT p.id AS preid, p.title, p.source, p.searched
							FROM predb p
							WHERE LENGTH(title) >= 15 AND title NOT REGEXP "[\"\<\> ]"
							AND searched = 0
							AND DATEDIFF(NOW(), predate) > 1
							ORDER BY predate ASC
							LIMIT %s
							OFFSET %s',
							$maxperrun,
							$thread * $maxperrun - $maxperrun
						)
			);

			if ($pres instanceof \Traversable) {
				foreach ($pres as $pre) {
					$namefixer->done = $namefixer->matched = false;
					$ftmatched = $searched = 0;
					$ftmatched = $namefixer->matchPredbFT($pre, 1, 1, true, 1);
					if ($ftmatched > 0) {
						$searched = 1;
					} elseif ($ftmatched < 0) {
						$searched = -6;
						echo "*";
					} else {
						$searched = $pre['searched'] - 1;
						echo ".";
					}
					$pdo->queryExec(sprintf("UPDATE predb SET searched = %d WHERE id = %d", $searched, $pre['preid']));
					$namefixer->checked++;
				}
			}
	}
}
