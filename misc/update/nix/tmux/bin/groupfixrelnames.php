<?php
require_once realpath(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\Category;
use nzedb\MiscSorter;
use nzedb\NameFixer;
use nzedb\Nfo;
use nzedb\NNTP;
use nzedb\NZB;
use nzedb\NZBContents;
use nzedb\db\Settings;
use nzedb\processing\PostProcess;

$pdo = new Settings();

if (!isset($argv[1])) {
	exit($pdo->log->error("This script is not intended to be run manually, it is called from Multiprocessing."));
} else if (isset($argv[1])) {
	$namefixer = new NameFixer(['Settings' => $pdo]);
	$sorter = new MiscSorter(true, $pdo);
	$pieces = explode(' ', $argv[1]);
	$guidChar = $pieces[1];
	$maxperrun = $pieces[2];
	$thread = $pieces[3];

	switch (true) {

		case $pieces[0] === 'standard' && isset($guidChar) && isset($maxperrun) && is_numeric($maxperrun):

			// Find releases to process.  We only want releases that have no PreDB match, have not been renamed, exist
			// in Other Categories, have already been PP Add/NFO processed, and haven't been fully fixRelName processed
			$releases = $pdo->queryDirect(
				sprintf("
					SELECT
						r.id AS releases_id, r.guid, r.groups_id, r.categories_id, r.name, r.searchname, r.proc_nfo,
						r.proc_files, r.proc_par2, r.proc_sorter, r.ishashed, r.dehashstatus, r.nfostatus,
						r.size AS relsize, r.predb_id,
						IFNULL(rf.releases_id, '') AS fileid, IF(rf.ishashed = 1, rf.name, '') AS filehash,
						IFNULL(GROUP_CONCAT(rf.name ORDER BY rf.name ASC SEPARATOR '|'), '') AS filestring,
						IFNULL(UNCOMPRESS(rn.nfo), '') AS textstring,
						IFNULL(HEX(ru.uniqueid), '') AS uid
					FROM releases r
					LEFT JOIN release_nfos rn ON r.id = rn.releases_id
					LEFT JOIN release_files rf ON r.id = rf.releases_id
					LEFT JOIN release_unique ru ON ru.releases_id = r.id
					WHERE r.leftguid = %s
					AND r.nzbstatus = %d
					AND r.isrenamed = %d
					AND r.predb_id = 0
					AND r.passwordstatus >= 0
					AND r.nfostatus > %d
					AND
					(
						r.proc_nfo = %d
						OR r.proc_files = %d
						OR r.proc_uid = %d
						OR r.proc_par2 = %d
						OR r.proc_sorter = %d
						OR r.dehashstatus BETWEEN -6 AND 0
					)
					AND r.categories_id IN (%s)
					GROUP BY r.id
					LIMIT %s",
					$pdo->escapeString($guidChar),
					NZB::NZB_ADDED,
					NameFixer::IS_RENAMED_NONE,
					Nfo::NFO_UNPROC,
					NameFixer::PROC_NFO_NONE,
					NameFixer::PROC_FILES_NONE,
					NameFixer::PROC_UID_NONE,
					NameFixer::PROC_PAR2_NONE,
					MiscSorter::PROC_SORTER_NONE,
					Category::getCategoryOthersGroup(),
					$maxperrun
				)
			);

			if ($releases instanceof \Traversable) {

				foreach ($releases as $release) {

					$namefixer->checked++;
					$namefixer->done = $namefixer->matched = false;

					echo PHP_EOL . $pdo->log->primaryOver("[{$release['releases_id']}]");

					if ($release['ishashed'] == 1 && $release['dehashstatus'] > -6 && $release['dehashstatus'] < 0) {
						echo $pdo->log->primaryOver('m');
						if (preg_match('/[a-fA-F0-9]{32,40}/i', $release['name'], $matches)) {
							$namefixer->matchPredbHash($matches[0], $release, 1, 1, true, 1);
						}
						if ($namefixer->matched === false
							&& !empty($release['filehash'])
							&& preg_match('/[a-fA-F0-9]{32,40}/i', $release['filehash'], $matches)) {
							$namefixer->matchPredbHash($matches[0], $release, 1, 1, true, 1);
						}
					}
					$namefixer->_updateSingleColumn('dehashstatus', 'dehashstatus -1', $release['releases_id']);

					if($namefixer->matched) {
						continue;
					}
					$namefixer->done = $namefixer->matched = false;

					if ($release['proc_uid'] == NameFixer::PROC_UID_NONE
						&& !empty($release['uid'])) {
						echo $pdo->log->primaryOver('U');
						$namefixer->uidCheck($release, true, 'UID, ', 1, 1);
					}
					$namefixer->_updateSingleColumn('proc_uid', NameFixer::PROC_UID_DONE, $release['releases_id']);

					if($namefixer->matched) {
						continue;
					}
					$namefixer->done = $namefixer->matched = false;

					if (!preg_match('/^=newz\[NZB\]=\w+/', $release['textstring'])
						|| $release['nfostatus'] == Nfo::NFO_FOUND
						|| $release['nfostatus'] == NameFixer::PROC_NFO_NONE) {
						echo $pdo->log->primaryOver('n');
						$namefixer->done = $namefixer->matched = false;
						$namefixer->checkName($release, true, 'NFO, ', 1, 1);
					}
					$namefixer->_updateSingleColumn('proc_nfo', NameFixer::PROC_NFO_DONE, $release['releases_id']);

					if($namefixer->matched) {
						continue;
					}
					$namefixer->done = $namefixer->matched = false;

					if ($release['fileid'] > 0 && $release['proc_files'] == NameFixer::PROC_FILES_NONE) {
						echo $pdo->log->primaryOver('F');
						$namefixer->done = $namefixer->matched = false;
						$fileNames = explode('|', $release['filestring']);
						if (is_array($fileNames)) {
							$releaseFile = $release;
							foreach ($fileNames AS $fileName) {
								$releaseFile['texstring'] = $fileName;
								$namefixer->checkName($releaseFile, true, 'Filenames, ', 1, 1);
							}
						}
					}
					$namefixer->_updateSingleColumn('proc_files', NameFixer::PROC_FILES_DONE, $release['releases_id']);

					if($namefixer->matched) {
						continue;
					}
					$namefixer->done = $namefixer->matched = false;

					if ($release['proc_par2'] == NameFixer::PROC_PAR2_NONE) {
						echo $pdo->log->primaryOver('p');
						if (!isset($nzbcontents)) {
							$nntp = new NNTP(['Settings' => $pdo]);
							if (($pdo->getSetting('alternate_nntp') == '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
								$pdo->log->error("Unable to connect to usenet.");
							}
							$Nfo = new Nfo(['Settings' => $pdo, 'Echo' => true]);
							$nzbcontents = new NZBContents(
								[
									'Echo' => true, 'NNTP' => $nntp, 'Nfo' => $Nfo, 'Settings' => $pdo,
									'PostProcess' => new PostProcess(['Settings' => $pdo, 'Nfo' => $Nfo, 'NameFixer' => $namefixer])
								]
							);
						}
						$nzbcontents->checkPAR2($release['guid'], $release['releases_id'], $release['groups_id'], 1, 1);
					}
					$namefixer->_updateSingleColumn('proc_par2', NameFixer::PROC_PAR2_DONE, $release['releases_id']);

					if($namefixer->matched) {
						continue;
					}
					$namefixer->done = $namefixer->matched = false;

					if ($release['nfostatus'] == Nfo::NFO_FOUND
						&& $release['proc_sorter'] === MiscSorter::PROC_SORTER_NONE) {
							$res = $sorter->nfosorter(null, $release['releases_id']);
						}
					}
				}
			break;

		case $pieces[0] === 'predbft' && isset($maxperrun) && is_numeric($maxperrun) && isset($thread) && is_numeric($thread):
			$pres = $pdo->queryDirect(
				sprintf('
					SELECT p.id AS predb_id, p.title, p.source, p.searched
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
					$pdo->queryExec(
						sprintf("
							UPDATE predb
							SET searched = %d
							WHERE id = %d",
							$searched,
							$pre['predb_id']
						)
					);
					$namefixer->checked++;
				}
			}
	}
}
