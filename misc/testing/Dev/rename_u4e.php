<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use \nzedb\processing\PostProcess;

$pdo = new nzedb\db\Settings();

$tmpPath = $pdo->getSetting('tmpunrarpath');

if (empty($tmpPath)) {
	exit ('The tmpunrarpath site setting must not be empty!' . PHP_EOL);
}

if (substr($tmpPath, -1) !== DS) {
	$tmpPath .= DS;
}

$tmpPath .= 'u4e' . DS;

if (!is_dir($tmpPath)) {
	$old = umask(0777);
	@mkdir($tmpPath, 0777, true);
	@chmod($tmpPath, 0777);
	@umask($old);
	if (!is_dir($tmpPath)) {
		exit('Unable to create temp directory:' . $tmpPath . PHP_EOL);
	}
}

$unrarPath = $pdo->getSetting('unrarpath');

if (empty($unrarPath)) {
	exit ('The site setting for the unrar path must not be empty!' . PHP_EOL);
}

$nntp = new NNTP(['Settings' => $pdo]);
$nfo = new Nfo(['Echo' => true, 'Settings' => $pdo]);
$nzbContents= new NZBContents(
	array(
		'Settings' => $pdo,
		'Echo' => true,
		'Nfo' => $nfo,
		'PostProcess' => new PostProcess(['Settings' => $pdo, 'Nfo' => $nfo]),
		'NNTP' => $nntp
	)
);
$categorize = new Categorize(['Settings' => $pdo]);

$releases = $pdo->queryDirect(
	sprintf('
		SELECT rf.name AS filename, r.categoryid, r.name, r.guid, r.id, r.group_id, r.postdate, r.searchname AS oldname, g.name AS groupname
		FROM releasefiles rf
		INNER JOIN releases r ON rf.releaseid = r.id
		INNER JOIN groups g ON r.group_id = g.id
		WHERE (r.isrenamed = 0 OR r.categoryid = 7020)
		AND r.passwordstatus = 0
		AND rf.name %s
		ORDER BY r.postdate DESC',
		$pdo->likeString('Linux_2rename.sh')
	)
);

if ($releases instanceof Traversable) {

	$nntp->doConnect();

	$sphinx = new SphinxSearch();

	foreach($releases as $release) {

		// Clear old files.
		foreach (glob($tmpPath . '*') as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}

		// Load up the NZB as a XML file.
		$nzbXML = $nzbContents->LoadNZB($release['guid']);
		if ($nzbXML === false) {
			continue;
		}

		// Try to get the first RAR message-id.
		$messageID = '';
		foreach($nzbXML->file as $file) {
			if (preg_match('/part0*1\.rar/i', (string)$file->attributes()->subject)) {
				$messageID = (string)$file->segments->segment;
				break;
			}
		}

		// If we didn't find a messageID, try again with a less strict regex.
		if ($messageID === '') {
			foreach($nzbXML->file as $file) {
				if (preg_match('/\.r(ar|0[01])/i', (string)$file->attributes()->subject)) {
					$messageID = (string)$file->segments->segment;
					break;
				}
			}
		}

		if ($messageID === '') {
			echo 'ERROR: Could not find the message-id for the rar file' . PHP_EOL;
			continue;
		}

		$sampleBinary = $nntp->getMessages($release['groupname'], $messageID);
		if ($sampleBinary === false) {
			echo 'ERROR: Could not fetch the binary from usenet.' . PHP_EOL;
			continue;
		} else {
			@file_put_contents($tmpPath . 'u4e_l2r.rar', $sampleBinary);
		}

		if (!is_file($tmpPath . 'u4e_l2r.rar')) {
			echo 'ERROR: Could not write RAR file to temp folder!' . PHP_EOL;
			continue;
		}

		// Extract the RAR file.
		nzedb\utility\runCmd(
			'"' .
			$unrarPath .
			'" e -ai -ep -c- -id -inul -kb -or -p- -r -y "' .
			$tmpPath . 'u4e_l2r.rar" "' .
			$tmpPath . '"'
		);

		@unlink($tmpPath . 'u4e_l2r.rar');

		$files = scandir($tmpPath);
		if ($files === false) {
			echo 'ERROR: Could not get list of files in temp folder!' . PHP_EOL;
			continue;
		}

		$fileName = '';
		foreach ($files as $file) {
			if (preg_match('/linux.*\.sh/i', $file)) {
				$fileName = $file;
				break;
			}
		}

		if ($fileName === '') {
			echo 'ERROR: Could not find Linux_2rename.sh in the temp folder!' . PHP_EOL;
			continue;
		}

		if (!is_file($tmpPath . $fileName)) {
			echo 'ERROR: The Linux_2rename.sh does not exist!' . PHP_EOL;
			@unlink($tmpPath . $fileName);
			continue;
		}

		$newName = '';
		$handle = @fopen($tmpPath . $fileName, 'r');
		if ($handle) {
			while (($buffer = fgets($handle, 16384)) !== false) {
				if (stripos($buffer, 'mkdir') !== false) {
					$newName = trim(str_replace('mkdir', '', $buffer));
					break;
				}
			}
			fclose($handle);
		}
		@unlink($tmpPath . $fileName);

		if ($newName === '') {
			echo 'ERROR: New name is empty!' . PHP_EOL;
			continue;
		}

		$determinedCat = $categorize->determineCategory($newName, $release['group_id']);

		NameFixer::echoChangedReleaseName(array(
				'new_name'     => $newName,
				'old_name'     => $release['oldname'],
				'new_category' => $categorize->getNameByid($determinedCat),
				'old_category' => $categorize->getNameByid($release['categoryid']),
				'group'        => $release['groupname'],
				'release_id'   => $release['id'],
				'method'       => 'misc/testing/Dev/rename_u4e.php'
			)
		);

		$newName = $pdo->escapeString(substr($newName, 0, 255));
		$pdo->queryExec(
			sprintf('
				UPDATE releases
					SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL,
						tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL,
						consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, preid = 0,
						searchname = %s, isrenamed = 1, iscategorized = 1, proc_files = 1, categoryid = %d
					WHERE id = %d',
				$newName,
				$determinedCat,
				$release['id']
			)
		);
		$sphinx->updateReleaseSearchName($release['id'], $newName);
	}
}
