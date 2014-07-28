<?php

use nzedb\db\Settings;

/**
 * Import NZB files into the database.
 * Class NZBImport
 */
class NZBImport
{
	/**
	 * @var \nzedb\db\Settings
	 * @access protected
	 */
	protected $pdo;

	/**
	 * @var Binaries
	 * @access protected
	 */
	protected $binaries;

	/**
	 * @var ReleaseCleaning
	 * @access protected
	 */
	protected $releaseCleaner;

	/**
	 * @var bool|stdClass
	 * @access protected
	 */
	protected $site;

	/**
	 * @var int
	 * @access protected
	 */
	protected $crossPost;

	/**
	 * @var Category
	 * @access protected
	 */
	protected $category;

	/**
	 * List of all the group names/ids in the DB.
	 * @var array
	 * @access protected
	 */
	protected $allGroups;

	/**
	 * Was this run from the browser?
	 * @var bool
	 * @access protected
	 */
	protected $browser;

	/**
	 * Return value for browser.
	 * @var string
	 * @access protected
	 */
	protected $retVal;

	/**
	 * Guid of the current releases.
	 * @var string
	 * @access protected
	 */
	protected $relGuid;

	/**
	 * Construct.
	 *
	 * @param bool $browser Was this started from the browser?
	 * @param bool $echo    Echo to CLI?
	 *
	 * @access public
	 */
	public function __construct($browser = false, $echo = true)
	{
		$this->pdo = new Settings();
		$this->binaries = new Binaries();
		$this->category = new Categorize();
		$this->nzb = new NZB($this->pdo);
		$this->releaseCleaner = new ReleaseCleaning();

		$this->crossPostt = ($this->pdo->getSetting('crossposttime') != '') ? $this->pdo->getSetting('crossposttime') : 2;
		$this->browser = $browser;
		$this->retVal = '';
		$this->echoCLI = (!$this->browser && nZEDb_ECHOCLI && $echo);
	}

	/**
	 * @param array $filesToProcess List of NZB files to import.
	 * @param bool|string $useNzbName Use the NZB file name as release name?
	 * @param bool $delete Delete the NZB when done?
	 * @param bool $deleteFailed Delete the NZB if failed importing?
	 *
	 * @return string|bool
	 *
	 * @access public
	 */
	public function beginImport($filesToProcess, $useNzbName = false, $delete = true, $deleteFailed = true)
	{
		// Get all the groups in the DB.
		if (!$this->getAllGroups()) {
			if ($this->browser) {
				return $this->retVal;
			} else {
				return false;
			}
		}

		$start = date('Y-m-d H:i:s');
		$nzbsImported = $nzbsSkipped = 0;

		// Loop over the file names.
		foreach ($filesToProcess as $nzbFile) {

			// Check if the file is really there.
			if (is_file($nzbFile)) {

				// Get the contents of the NZB file as a string.
				if (strtolower(substr($nzbFile, -7)) === '.nzb.gz') {
					$nzbString = $this->deZipNzb($nzbFile);
				} else {
					$nzbString = file_get_contents($nzbFile);
				}

				if ($nzbString === false) {
					$this->echoOut('ERROR: Unable to read: ' . $nzbFile);

					if ($deleteFailed) {
						@unlink($nzbFile);
					}
					$nzbsSkipped++;
					continue;
				}

				// Load it as a XML object.
				$nzbXML = @simplexml_load_string($nzbString);
				if ($nzbXML === false || strtolower($nzbXML->getName()) != 'nzb') {
					$this->echoOut('ERROR: Unable to load NZB XML data: ' . $nzbFile);

					if ($deleteFailed) {
						@unlink($nzbFile);
					}
					$nzbsSkipped++;
					continue;
				}

				// Try to insert the NZB details into the DB.
				$inserted = $this->scanNZBFile($nzbXML, ($useNzbName ? str_ireplace('.nzb', '', basename($nzbFile)) : false));

				if ($inserted) {

					// Try to copy the NZB to the NZB folder.
					$path = $this->nzb->getNZBPath($this->relGuid, $this->pdo->getSetting('nzbsplitlevel'), true);

					// Try to compress the NZB file in the NZB folder.
					$fp = gzopen ($path, 'w5');
					gzwrite ($fp, $nzbString);
					gzclose($fp);

					if (!is_file($path)) {
						$this->echoOut('ERROR: Problem compressing NZB file to: ' . $path);

						// Remove the release.
						$this->pdo->queryExec(
							sprintf("DELETE FROM releases WHERE guid = %s", $this->pdo->escapeString($this->relGuid))
						);

						if ($deleteFailed) {
							@unlink($nzbFile);
						}
						$nzbsSkipped++;
						continue;

					} else {

						if ($delete) {
							// Remove the nzb file.
							@unlink($nzbFile);
						}

						$nzbsImported++;
						continue;
					}

				} else {
					if ($deleteFailed) {
						@unlink($nzbFile);
					}
					$nzbsSkipped++;
					continue;
				}

			} else {
				$this->echoOut('ERROR: Unable to fetch: ' . $nzbFile);
				$nzbsSkipped++;
				continue;
			}
		}
		$this->echoOut(
			'Proccessed ' .
			$nzbsImported .
			' NZBs in ' .
			(strtotime(date('Y-m-d H:i:s')) - strtotime($start)) .
			' seconds, ' .
			$nzbsSkipped .
			' NZBs were skipped.'
		);

		if ($this->browser) {
			return $this->retVal;
		} else {
			return true;
		}
	}

	/**
	 * Decompress a gzip'ed NZB.
	 * @param string $path Path to the zipped NZB.
	 *
	 * @return string|bool
	 *
	 * @access protected
	 */
	protected function deZipNzb($path)
	{
		// String to hold the NZB contents.
		$string = '';

		// Open the gzip file.
		$nzb = @gzopen($path, 'rb', 0);
		if ($nzb) {
			// Append the decompressed data to the string until we find the end of file pointer.
			while (!gzeof($nzb)) {
				$string .= gzread($nzb, 1024);
			}
			// Close the gzip file.
			gzclose($nzb);
		}
		// Return the string.
		return ($string === '' ? false : $string);
	}

	/**
	 * @param object $nzbXML Reference of simpleXmlObject with NZB contents.
	 * @param bool|string $useNzbName Use the NZB file name as release name?
	 * @return bool
	 *
	 * @access protected
	 */
	protected function scanNZBFile(&$nzbXML, $useNzbName = false)
	{
		$totalFiles = $totalSize = $groupID = 0;
		$isBlackListed = $groupName = $firstName = $posterName = $postDate = false;

		// Go through the NZB, get the details, look if it's blacklisted, look if we have the groups.
		foreach ($nzbXML->file as $file) {

			$totalFiles++;
			$groupID = -1;

			// Get the nzb info.
			if ($firstName === false ) {
				$firstName =(string) $file->attributes()->subject;
			}
			if ($posterName === false) {
				$posterName = (string) $file->attributes()->poster;
			}
			if ($postDate === false) {
				$postDate = date("Y-m-d H:i:s", (string) $file->attributes()->date);
			}

			// Make a fake message array to use to check the blacklist.
			$msg = array("Subject" => (string) $file->attributes()->subject, "From" => (string) $file->attributes()->poster, "Message-ID" => "");

			// Get the group names, group_id, check if it's blacklisted.
			$groupArr = array();
			foreach ($file->groups->group as $group) {
				$group = (string) $group;

				// If group_id is -1 try to get a group_id.
				if ($groupID === -1) {
					if (array_key_exists($group, $this->allGroups)) {
						$groupID = $this->allGroups[$group];
						if (!$groupName) {
							$groupName = $group;
						}
					}
				}
				// Add all the found groups to an array.
				$groupArr[] = $group;

				// Check if this NZB is blacklisted.
				if ($this->binaries->isBlacklisted($msg, $group)) {
					$isBlackListed = true;
				}
			}

			// If we found a group and it's not blacklisted.
			if ($groupID !== -1 && !$isBlackListed) {

				// Get the size of the release.
				if (count($file->segments->segment) > 0) {
					foreach ($file->segments->segment as $segment) {
						$totalSize += (int)$segment->attributes()->bytes;
					}
				}

			} else {
				if ($isBlackListed) {
					$errorMessage = "Subject is blacklisted: " . utf8_encode(trim($firstName[$totalFiles]));
				} else {
					$errorMessage = "No group found for " . $firstName[$totalFiles] . " (one of " . implode(', ', $groupArr) . " are missing";
				}
				$this->echoOut($errorMessage);

				return false;
			}
		}

		// Try to insert the NZB details into the DB.
		return $this->insertNZB(
			array(
				'subject'    => $firstName,
				'useFName'   => $useNzbName,
				'postDate'   => (empty($postDate) ? date("Y-m-d H:i:s") : $postDate),
				'from'       => (empty($posterName) ? '' : $posterName),
				'group_id'    => $groupID,
				'groupName'  => $groupName,
				'totalFiles' => $totalFiles,
				'totalSize'  => $totalSize
			)
		);
	}

	/**
	 * Insert the NZB details into the database.
	 *
	 * @param $nzbDetails
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function insertNZB($nzbDetails)
	{
		// Make up a GUID for the release.
		$this->relGuid = sha1(uniqid('', true) . mt_rand());

		// Remove part count from subject.
		$partLess = preg_replace('/(\(\d+\/\d+\))*$/', 'yEnc', $nzbDetails['subject']);
		// Remove added yEnc from above and anything after.
		$subject = utf8_encode(trim(preg_replace('/yEnc.*$/i', 'yEnc', $partLess)));

		$renamed = 0;
		if ($nzbDetails['useFName']) {
			// If the user wants to use the file name.. use it.
			$cleanName = $nzbDetails['useFName'];
			$renamed = 1;
		} else {
			// Pass the subject through release cleaner to get a nicer name.
			$cleanName = $this->releaseCleaner->releaseCleaner($subject, $nzbDetails['from'], $nzbDetails['totalSize'], $nzbDetails['groupName']);
			if (isset($cleanName['properlynamed'])) {
				$cleanName = $cleanName['cleansubject'];
				$renamed = (isset($cleanName['properlynamed']) && $cleanName['properlynamed'] === true ? 1 : 0);
			}
		}

		// Look for a duplicate on name, poster and size.
		$dupeCheck = $this->pdo->queryOneRow(
			sprintf(
				'SELECT id FROM releases WHERE name = %s AND fromname = %s AND size BETWEEN %s AND %s',
				$this->pdo->escapeString($subject),
				$this->pdo->escapeString($nzbDetails['from']),
				$this->pdo->escapeString($nzbDetails['totalSize'] * 0.99),
				$this->pdo->escapeString($nzbDetails['totalSize'] * 1.01)
			)
		);

		if ($dupeCheck === false) {
			// Insert the release into the DB.
			$relID = $this->pdo->queryInsert(
				sprintf(
					"INSERT INTO releases
						(name, searchname, totalpart, group_id, adddate, guid, rageid, postdate, fromname,
						size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, isrenamed, iscategorized)
					 VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, %d, 1)",
					$this->pdo->escapeString($subject),
					$this->pdo->escapeString($cleanName),
					$nzbDetails['totalFiles'],
					$nzbDetails['group_id'],
					$this->pdo->escapeString($this->relGuid),
					$this->pdo->escapeString($nzbDetails['postDate']),
					$this->pdo->escapeString($nzbDetails['from']),
					$this->pdo->escapeString($nzbDetails['totalSize']),
					($this->pdo->getSetting('checkpasswordedrar') == "1" ? -1 : 0),
					$this->category->determineCategory($cleanName, $nzbDetails['group_id']),
					$renamed
				)
			);
		} else {
			$this->echoOut('This release is already in our DB so skipping: ' . $subject);
			return false;
		}

		if (isset($relID) && $relID === false) {
			$this->echoOut('ERROR: Problem inserting: ' . $subject);
			return false;
		}
		return true;
	}
	/**
	 * Get all groups in the DB.
	 * @return bool
	 *
	 * @access protected
	 */
	protected function getAllGroups()
	{
		$this->allGroups = array();
		$groups = $this->pdo->query("SELECT id, name FROM groups");
		foreach ($groups as $group) {
			$this->allGroups[$group["name"]] = $group["id"];
		}

		if (count($this->allGroups) === 0) {
			$this->echoOut('You have no groups in your database!');
			return false;
		}
		return true;
	}

	/**
	 * Echo message to browser or CLI.
	 * @param $message
	 *
	 * @access protected
	 */
	protected function echoOut($message)
	{
		if ($this->browser) {
			$this->retVal .= $message . "<br />";
		} elseif ($this->echoCLI) {
			echo $message . PHP_EOL;
		}
	}
}
