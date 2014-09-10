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
	protected $crossPostt;

	/**
	 * @var Categorize
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
	 * @var bool
	 */
	public $echoCLI;

	/**
	 * @var NZB
	 */
	public $nzb;

	/**
	 * Construct.
	 *
	 * @param array $options Class instances / various options.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Browser'          => false, // Was this started from the browser?
			'Echo'             => true,  // Echo to CLI?
			'Binaries'         => null,
			'Categorize'       => null,
			'NZB'              => null,
			'ReleaseCleaning'  => null,
			'Releases'         => null,
			'Settings'         => null,
		];
		$options += $defaults;

		$this->echoCLI = (!$this->browser && nZEDb_ECHOCLI && $options['Echo']);
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->binaries = ($options['Binaries'] instanceof \Binaries ? $options['Binaries'] : new \Binaries(['Settings' => $this->pdo, 'Echo' => $this->echoCLI]));
		$this->category = ($options['Categorize'] instanceof \Categorize ? $options['Categorize'] : new \Categorize(['Settings' => $this->pdo]));
		$this->nzb = ($options['NZB'] instanceof \NZB ? $options['NZB'] : new \NZB($this->pdo));
		$this->releaseCleaner = ($options['ReleaseCleaning'] instanceof \ReleaseCleaning ? $options['ReleaseCleaning'] : new \ReleaseCleaning($this->pdo));
		$this->releases = ($options['Releases'] instanceof \Releases ? $options['Releases'] : new \Releases(['settings' => $this->pdo]));

		$this->crossPostt = ($this->pdo->getSetting('crossposttime') != '') ? $this->pdo->getSetting('crossposttime') : 2;
		$this->browser = $options['Browser'];
		$this->retVal = '';
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
					$nzbString = nzedb\utility\Utility::unzipGzipFile($nzbFile);
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
					$path = $this->nzb->getNZBPath($this->relGuid, 0, true);

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
					break;
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
					$errorMessage = "Subject is blacklisted: " . utf8_encode(trim($firstName));
				} else {
					$errorMessage = "No group found for " . $firstName . " (one of " . implode(', ', $groupArr) . " are missing";
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
		$this->relGuid = $this->releases->createGUID();

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

		$escapedSubject = $this->pdo->escapeString($subject);
		$escapedFromName = $this->pdo->escapeString($nzbDetails['from']);

		// Look for a duplicate on name, poster and size.
		$dupeCheck = $this->pdo->queryOneRow(
			sprintf(
				'SELECT id FROM releases WHERE name = %s AND fromname = %s AND size BETWEEN %s AND %s',
				$escapedSubject,
				$escapedFromName,
				$this->pdo->escapeString($nzbDetails['totalSize'] * 0.99),
				$this->pdo->escapeString($nzbDetails['totalSize'] * 1.01)
			)
		);

		if ($dupeCheck === false) {
			$escapedSearchName = $this->pdo->escapeString($cleanName);
			// Insert the release into the DB.
			$relID = $this->releases->insertRelease(
				[
					'name' => $escapedSubject,
					'searchname' => $escapedSearchName,
					'totalpart' => $nzbDetails['totalFiles'],
					'group_id' => $nzbDetails['group_id'],
					'guid' => $this->pdo->escapeString($this->relGuid),
					'postdate' => $this->pdo->escapeString($nzbDetails['postDate']),
					'fromname' => $escapedFromName,
					'size' => $this->pdo->escapeString($nzbDetails['totalSize']),
					'categoryid' => $this->category->determineCategory($cleanName, $nzbDetails['group_id']),
					'isrenamed' => $renamed,
					'reqidstatus' => 0,
					'preid' => 0,
					'nzbstatus' => \NZB::NZB_ADDED
				]
			);
		} else {
			//$this->echoOut('This release is already in our DB so skipping: ' . $subject);
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
		$this->allGroups = [];
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
