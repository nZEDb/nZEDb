<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

$c = new ColorCLI();

if (!isset($argv[1]) && $argv[1] !== 'makeitso') {
	exit(
		$c->error("\nThis script is not currently operational and should not be run. If you must play around with it, then use makeitso as the argument.\n"
		. "If you do not understand programming or have the IQ of a quanset hut, I urge you to reconsider trying to do so.\n"
		. "Run this script with the syntax below at your own risk.\n\n"
		. "php $argv[0] makeitso                                     ...: To run rename on u4e.\n"
		)
	);
}

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
		SELECT rf.name AS filename, r.categoryid, r.name, r.guid, r.id, r.group_id, r.postdate, r.searchname AS oldname  g.name AS groupname
		FROM releasefiles rf
		INNER JOIN releases r ON rf.releaseid = rf.id
		INNER JOIN groups g ON r.group_id = g.id
		WHERE (r.isrenamed = 0 OR r.categoryid = 7020)
		AND r.passwordstatus = 0
		AND rf.name LIKE %s
		ORDER BY r.postdate DESC',
		$db->escapeString('%Linux_2rename.sh%')
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
			if (preg_match('/\.r(ar|00)/i', (string)$file->attributes()->subject)) {
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
		$unRarOutput = nzedb\utility\runCmd(
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

		if (!is_file($tmpPath . $fileName)) {
			echo 'ERROR: Could not get Linux_2rename.sh!' . PHP_EOL;
			continue;
		}

		$renameFile = @file_get_contents($tmpPath . $fileName);
		@unlink($tmpPath . $fileName);
		if ($renameFile === false) {
			echo 'ERROR: Unable to get contents of Linux_2rename.sh' . PHP_EOL;
			continue;
		}

		$newName = '';
		$handle = @fopen($tmpPath . $fileName, 'r');
		if ($handle) {
			while (($buffer = fgets($handle, 16384)) !== false) {
				if (stripos('mkdir', $buffer) !== false) {
					$newName = trim(str_replace('mkdir', '', $buffer));
					break;
				}
			}
			fclose($handle);
		}

		if ($newName === '') {
			echo 'ERROR: New name is empty!' . PHP_EOL;
			continue;
		}

		$newName = str_replace('mkdir ', '', $arr[1]);
		$determinedCat = $categorize->determineCategory($release['groupname'], $newName);

		if (isset($newName)) {
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
		} else {
			echo $c->error('Cannot Determine name for ' . $row['id']);
		}
	}
	$nntp->doQuit();
}