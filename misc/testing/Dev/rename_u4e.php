<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

$c = new ColorCLI();

$site = (new Sites())->get();
if (empty($site->tmpunrarpath)) {
	exit ('The tmpunrarpath site setting must not be empty!');
}
$tmpPath = $site->tmpunrarpath;
if (substr($site->tmpunrarpath, -1) !== DS) {
	$tmpPath .= DS;
}

if (empty($site->unrarpath)) {
	exit ('The site setting for the unrar path must not be empty!');
}

$db = new nzedb\db\DB();
$nntp = new NNTP;
$nzbContents= new NZBContents(
	array(
		'db' => $db,
		'echo' => true,
		'nfo' => new Nfo(true),
		'pp' => new PostProcess(true),
		'nntp' => $nntp
	)
);
$categorize = new Categorize();

$releases = $db->queryDirect(
	sprintf('
		SELECT rf.name AS filename, r.categoryid, r.name, r.guid, r.id, r.group_id, r.postdate, r.searchname AS oldname, g.name AS groupname
		FROM releasefiles rf
		INNER JOIN releases r ON rf.releaseid = r.id
		INNER JOIN groups g ON r.group_id = g.id
		WHERE (r.isrenamed = 0 OR r.categoryid = 7020)
		AND r.passwordstatus = 0
		AND rf.name %s
		ORDER BY r.postdate DESC',
		$db->likeString('Linux_2rename.sh')
	)
);

if ($releases !== false) {

	$nntp->doConnect();

	foreach($releases as $release) {

		// Load up the NZB as a XML file.
		$nzbXML = $nzbContents->LoadNZB($release['guid']);
		if ($nzbXML === false) {
			continue;
		}

		$messageID = '';
		foreach($nzbXML->file as $file) {
			if (preg_match('/part\d*1\.rar/i', (string)$file->attributes()->subject)) {
				$messageID = (string)$file->segments->segment;
				break;
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
			$site->unrarpath .
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

		echo
			PHP_EOL .
			$c->headerOver("New name:  ") . $c->primary($newName) .
			$c->headerOver("Old name:  ") . $c->primary($release['oldname']) .
			$c->headerOver("Use name:  ") . $c->primary($release['name']) .
			$c->headerOver("New cat:   ") . $c->primary($categorize->getNameByid($determinedCat)) .
			$c->headerOver("Old cat:   ") . $c->primary($categorize->getNameByid($release['categoryid'])) .
			$c->headerOver("Group:     ") . $c->primary($release['groupname']) .
			$c->headerOver("Method:    ") . $c->primary('Files, u4e') .
			$c->headerOver("ReleaseID: ") . $c->primary($release['id']);

		$db->queryExec(
			sprintf('
				UPDATE releases
				SET isrenamed = 1, searchname = %s, categoryid = %d
				WHERE id = %d',
				$db->escapeString(substr($newName, 0, 255)),
				$determinedCat,
				$release['id']
			)
		);
	}
	$nntp->doQuit();
}