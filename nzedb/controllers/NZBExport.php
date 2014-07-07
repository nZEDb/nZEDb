<?php

use nzedb\db\Settings;
use nzedb\utility;

/**
 * Export NZB's to a folder.
 * Class NZBExport
 */
class NZBExport
{
	/**
	 * Started from browser?
	 * @var bool
	 * @access protected
	 */
	protected $browser;

	/**
	 * @var string Return value on browser.
	 * @access protected
	 */
	protected $retVal;

	/**
	 * @var DB
	 * @access protected
	 */
	protected $pdo;

	/**
	 * @var NZB
	 * @access protected
	 */
	protected $nzb;

	/**
	 * @var Releases
	 * @access protected
	 */
	protected $releases;

	/**
	 * @var bool
	 * @access protected
	 */
	protected $echoCLI;

	/**
	 * @param bool $browser Started from browser?
	 * @param bool $echo    Echo to CLI?
	 *
	 * @access public
	 */
	public function __construct($browser=false, $echo = true)
	{
		$this->browser = $browser;
		$this->pdo = new Settings();
		$this->releases = new Releases();
		$this->nzb = new NZB();
		$this->echoCLI = (!$this->browser && nZEDb_ECHOCLI && $echo);
	}

	/**
	 * Export to user specified folder.
	 *
	 * @param array $params
	 *
	 * @return bool
	 *
	 * @access public
	 */
	public function beginExport($params)
	{
		$gzip = false;
		if ($params[4] === true) {
			$gzip = true;
		}

		$fromDate = $toDate = $groups = '';
		$path = $params[0];

		// Check if the path ends with dir separator.
		if (substr($path, -1) !== DS) {
			$path .= DS;
		}

		// Check if it's a directory.
		if (!is_dir($path)) {
			$this->echoOut('Folder does not exist: ' . $path);
			return $this->returnValue();
		}

		// Check if we can write to it.
		if (!is_writable($path)) {
			$this->echoOut('Folder is not writable: ' . $path);
			return $this->returnValue();
		}

		// Check if the from date is the proper format.
		if (isset($params[1]) && $params[1] !== '') {
			if (!$this->checkDate($params[1])) {
				return $this->returnValue();
			}
			$fromDate = $params[1];
		}

		// Check if the to date is the proper format.
		if (isset($params[2]) && $params[2] !== '') {
			if (!$this->checkDate($params[2])) {
				return $this->returnValue();
			}
			$toDate = $params[2];
		}

		// Check if the group_id exists.
		if (isset($params[3]) && $params[3] !== 0) {
			if (!is_numeric($params[3])) {
				$this->echoOut('The group ID is not a number: ' . $params[3]);
				return $this->returnValue();
			}
			$groups = $this->pdo->query('SELECT id, name FROM groups WHERE id = ' . $params[3]);
			if (count($groups) === 0) {
				$this->echoOut('The group ID is not in the DB: ' . $params[3]);
				return $this->returnValue();
			}
		} else {
			$groups = $this->pdo->query('SELECT id, name FROM groups');
		}

		$exported = $currentExport = 0;
		// Loop over groups to take less RAM.
		foreach ($groups as $group) {
			$currentExport = 0;
			// Get all the releases based on the parameters.
			$releases = $this->releases->getForExport($fromDate, $toDate, $group['id']);
			$totalFound = count($releases);
			if ($totalFound === 0) {
				if ($this->echoCLI) {
					echo 'No releases found to export for group: ' . $group['name'] . PHP_EOL;
				}
				continue;
			}
			if ($this->echoCLI) {
				echo 'Found ' . $totalFound . ' releases to export for group: ' . $group['name'] . PHP_EOL;
			}

			// Create a path to store the new NZB files.
			$currentPath = $path . $this->safeFilename($group['name']) . DS;
			if (!is_dir($currentPath)) {
				mkdir($currentPath);
			}
			foreach ($releases as $release) {

				// Get path to the NZB file.
				$nzbFile = $this->nzb->NZBPath($release["guid"]);
				// Check if it exists.
				if ($nzbFile === false) {
					if ($this->echoCLI) {
						echo 'Unable to find NZB for release with GUID: ' . $release['guid'];
					}
					continue;
				}

				// Create path to current file.
				$currentFile = $currentPath . $this->safeFilename($release['searchname']);

				// Check if the user wants them in gzip, copy it if so.
				if ($gzip) {
					if (!copy($nzbFile, $currentFile . '.nzb.gz')) {
						if ($this->echoCLI) {
							echo 'Unable to export NZB with GUID: ' . $release['guid'];
						}
						continue;
					}
				// If not, decompress it and create a file to store it in.
				} else {
					ob_start();
					if (!@readgzfile($nzbFile)) {
						if ($this->echoCLI) {
							echo 'Unable to export NZB with GUID: ' . $release['guid'];
						}
						continue;
					}
					$nzbContents = ob_get_contents();
					ob_end_clean();
					$fh = fopen($currentFile . '.nzb', 'w');
					fwrite($fh, $nzbContents);
					fclose($fh);
				}

				$currentExport++;

				if ($this->echoCLI && $currentExport % 10 === 0) {
					echo 'Exported ' . $currentExport . ' of ' . $totalFound . ' nzbs for group: ' . $group['name'] . "\r";
				}
			}
			if ($this->echoCLI && $currentExport > 0) {
				echo 'Exported ' . $currentExport . ' of ' . $totalFound . ' nzbs for group: ' . $group['name'] . PHP_EOL;
			}
			$exported += $currentExport;
		}
		if ($exported > 0) {
			$this->echoOut('Exported total of ' . $exported . ' NZB files to ' . $path);
		}

		return $this->returnValue();
	}

	/**
	 * Return bool on CLI, string on browser.
	 * @return bool|string
	 *
	 * @access protected
	 */
	protected function returnValue()
	{
		return ($this->browser ? $this->retVal : true);
	}

	/**
	 * Check if date is in good format.
	 *
	 * @param string $date
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function checkDate($date)
	{
		if (!preg_match('/^(\d{2}\/){2}\d{4}$/', $date)) {
			$this->echoOut('Wrong date format: ' . $date);
			return false;
		}
		return true;
	}

	/**
	 * Echo message to browser or CLI.
	 *
	 * @param string $message
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

	/**
	 * Remove unsafe chars from a filename.
	 *
	 * @param string $filename
	 *
	 * @return string
	 *
	 * @access protected
	 */
	protected function safeFilename($filename)
	{
		return trim(preg_replace('/[^\w\s.-]*/i', '', $filename));
	}
}
