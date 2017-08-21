<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2016 nZEDb
 */
namespace app\extensions\util;

use app\models\Settings;
use lithium\core\ConfigException;
use nzedb\utility\Misc;

class Versions extends \lithium\core\Object
{
	/**
	 * These constants are bitwise for checking what has changed.
	 */
	const UPDATED_GIT_TAG		= 1;
	const UPDATED_SQL_DB_PATCH	= 2;
	const UPDATED_SQL_FILE_LAST	= 4;

	/**
	 * @var integer Bitwise mask of elements that have been changed.
	 */
	protected $changes = 0;

	/**
	 * @var \app\extensions\util\Git object.
	 */
	protected $git;

	/**
	 * @var \simpleXMLElement object.
	 */
	protected $versions = null;

	/**
	 * @var \simpleXMLElement object
	 */
	protected $xml = null;

	public function __construct(array $config = [])
	{
		$defaults = [
			'git'	=> null,
			'path'	=> nZEDb_VERSIONS,
		];

		parent::__construct($config += $defaults);
	}

	public function checkGitTag($update = false)
	{
		$this->checkGitTagInFile($update);
	}

	/**
	 * Checks the git's latest version tag against the XML's stored value. Version should be
	 * Major.Minor.Revision[.fix][-dev|-RCx]
	 *
	 * @return string|false version string if matched or false.
	 */
	public function checkGitTagInFile($update = false)
	{
		$this->initialiseGit();
		$result = preg_match(Misc::VERSION_REGEX, $this->git->tagLatest(), $matches) ? $matches['digits'] : false;

		if ($result !== false) {
			if (!$this->git->isStable($this->git->getBranch())) {
				$this->loadXMLFile();
				$result = preg_match(Misc::VERSION_REGEX, $this->versions->git->tag->__toString(),
					$matches) ? $matches['digits'] : false;
				if ($result !== false) {
					if (version_compare($matches['digits'], '0.0.0', '!=')) {
						$this->versions->git->tag = '0.0.0-dev';
						$this->changes |= self::UPDATED_GIT_TAG;
					}
				}

				$result = $this->versions->git->tag;
			} else {
				$result = $this->checkGitTagsAreEqual(['update' => $update]);
			}
		}

		return $result;
	}

	public function checkGitTagsAreEqual(array $options = [])
	{
		$options += [
			'update' => true,
			'verbose' => true,
		];

		$this->loadXMLFile();
		$latestTag = $this->git->tagLatest();

		// Check if file's entry is the same as current branch's tag
		if (version_compare($this->versions->git->tag, $latestTag, '!=')) {
			if ($options['update'] === true) {
				if ($options['verbose'] === true) {
					echo "Updating tag version to $latestTag" . PHP_EOL;
				}
				$this->versions->git->tag = $latestTag;
				$this->changes |= self::UPDATED_GIT_TAG;

				return $this->versions->git->tag;
			} else { // They're NOT the same but we were told not to update.
				if ($options['verbose'] === true) {
					echo "Current tag version $latestTag, skipping update!" . PHP_EOL;
				}
				return false;
			}
		} else { // They're the same so return true
			return true;
		}
	}

	/**
	 * Checks the database sqlpatch setting against the XML's stored value.
	 *
	 * @param boolean $verbose
	 *
	 * @return boolean|string The new database sqlpatch version, or false.
	 */
	public function checkSQLDb($verbose = true)
	{
		$this->loadXMLFile();
		$patch = $this->getSQLPatchFromDB();

		if ($this->versions->sql->db->__toString() != $patch) {
			if ($verbose) {
				echo "Updating Db revision to $patch" . PHP_EOL;
			}
			$this->versions->sql->db = $patch;
			$this->changes |= self::UPDATED_SQL_DB_PATCH;
		}

		return $this->isChanged(self::UPDATED_SQL_DB_PATCH) ? $patch : false;
	}

	public function checkSQLFileLatest($verbose = true)
	{
		$this->loadXMLFile();
		$lastFile = $this->getSQLPatchLast();

		if ($lastFile !== false && $this->versions->sql->file->__toString() != $lastFile) {
			if ($verbose === true) {
				echo "Updating latest patch file to $lastFile" . PHP_EOL;
			}
			$this->versions->sql->file = $lastFile;
			$this->changes |= self::UPDATED_SQL_FILE_LAST;
		}
/*
		if ($this->versions->sql->file->__toString() != $lastFile) {
			$this->versions->sql->file = $lastFile;
			$this->changes |= self::UPDATED_SQL_DB_PATCH;
		}*/
	}

	public function getGitBranch()
	{
		$this->initialiseGit();
		return $this->git->getBranch();
	}

	public function getGitHeadHash()
	{
		$this->initialiseGit();
		return $this->git->getHeadHash();
	}

	public function getGitTagInFile()
	{
		$this->loadXMLFile();
		return ($this->versions === null) ? null : $this->versions->git->tag->__toString();
	}

	public function getGitTagInRepo()
	{
		$this->initialiseGit();
		return $this->git->tagLatest();
	}

	public function getSQLPatchFromDB()
	{
		$dbVersion = Settings::value('..sqlpatch', true);

		if (!is_numeric($dbVersion)) {
			throw new \Exception('Bad sqlpatch value');
		}

		return $dbVersion;
	}

	public function getSQLPatchFromFile()
	{
		$this->loadXMLFile();
		return ($this->versions === null) ? null : $this->versions->sql->file->__toString();
	}

	public function getSQLPatchLast()
	{
		$options = [
			'data'  => nZEDb_RES . 'db' . DS . 'schema' . DS . 'data' . DS,
			'ext'   => 'sql',
			'path'  => nZEDb_RES . 'db' . DS . 'patches' . DS . 'mysql',
			'regex' => '#^' . Misc::PATH_REGEX . '(?P<patch>\d{4})~(?P<table>\w+)\.sql$#',
			'safe'  => true,
		];
		$files = Misc::getDirFiles($options);
		natsort($files);

		return (preg_match($options['regex'], end($files), $matches)) ? (int)$matches['patch'] : false;
	}

	public function getTagVersion()
	{
		$this->deprecated(__METHOD__, 'getGitTagInRepo');
		return $this->getGitTagInRepo();
	}

	public function getValidVersionsFile()
	{
		$this->loadXMLFile();
		return $this->xml;
	}

	/**
	 * Check whether the XML has been changed by one of the methods here.
	 *
	 * @return boolean True if the XML has been changed.
	 */
	public function hasChanged()
	{
		return $this->changes != 0;
	}

	public function save($verbose = true)
	{
		if ($this->hasChanged()) {
			if ($verbose === true && $this->changes > 0) {
				if ($this->isChanged(self::UPDATED_GIT_TAG)) {
					echo "Updated git tag version to " . $this->versions->git->tag . PHP_EOL;
				}

				if ($this->isChanged(self::UPDATED_SQL_DB_PATCH)) {
					echo "Updated Db SQL revision to " . $this->versions->sql->db . PHP_EOL;
				}

				if ($this->isChanged(self::UPDATED_SQL_FILE_LAST)) {
					echo "Updated latest SQL file to " . $this->versions->sql->file . PHP_EOL;
				}
			} else if ($this->changes == 0) {
				echo "Version file already up to date." . PHP_EOL;
			}
			$this->xml->asXML($this->_config['path']);
			$this->changes = false;
		}
	}

	protected function error($message)
	{
		// TODO handle console error message.
	}

	protected function initialiseGit()
	{

		if ($this->_config['git'] instanceof \app\extensions\util\Git) {
			$this->git =& $this->_config['git'];
		} else if (!($this->git instanceof \app\extensions\util\Git)) {
			try {
				$this->git = new \app\extensions\util\Git();
			} catch (\Exception $e) {
				throw new ConfigException("Unable to initialise Git object!");
			}
		}
	}

	protected function isChanged($property)
	{
		return (($this->changes & $property) == $property);
	}

	protected function loadXMLFile()
	{
		if (empty($this->versions)) {
			$temp = libxml_use_internal_errors(true);
			$this->xml = simplexml_load_file($this->_config['path']);
			libxml_use_internal_errors($temp);

			if ($this->xml === false) {
				$this->error(
					"Your versions XML file ({$this->_config['path']}) is broken, try updating from git."
				);
				throw new \Exception("Failed to open versions XML file '{$this->_config['path']}'");
			}

			if ($this->xml->count() > 0) {
				$vers = $this->xml->xpath('/nzedb/versions');

				if ($vers[0]->count() == 0) {
					$this->error("Your versions XML file ({$this->_config['path']}) does not contain version info, try updating from git.");
					throw new \Exception("Failed to find versions node in XML file '{$this->_config['path']}'");
				} else {
					$this->versions = &$this->xml->versions; // Create a convenience shortcut
				}
			} else {
				throw new \RuntimeException("No elements in file!\n");
			}
		}
	}

	protected function _init()
	{
		return parent::_init();
	}

	private function deprecated($methodOld, $methodUse)
	{
		trigger_error("This method ($methodOld) is deprecated. Please use '$methodUse' instead.",
			E_USER_NOTICE);
	}
}
