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
		$this->checkGitTagInFile();
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
		$ver = preg_match(Misc::VERSION_REGEX, $this->git->tagLatest(), $matches) ? $matches['all'] : false;

		if ($ver !== false) {
			if (!in_array($this->git->getBranch(), $this->git->getBranchesStable())) {
				$this->loadXMLFile();
				if (version_compare($this->versions->git->tag->__toString(), '0.0.0', '!=')) {
					$this->versions->git->tag = '0.0.0-dev';
					$this->changes |= self::UPDATED_GIT_TAG;
				}

				$ver = $this->versions->git->tag;
			} else {
				$ver = $this->checkGitTagsAreEqual($update);
			}
		}

		return $ver;
	}

	public function checkGitTagsAreEqual($update = true, $verbose = true)
	{
		$this->loadXMLFile();
		// Check if file's entry is the same as current branch's tag
		if (version_compare($this->versions->git->tag, $this->git->tagLatest(), '!=')) {
			if ($update === true) {
				//$this->out->primaryOver("Updating tag version to ") . $this->out->header($this->git->tagLatest());

				if ($verbose === true) {
					echo "Updating tag version to {$this->git->tagLatest()}" . PHP_EOL;
				}
				$this->versions->git->tag = $this->git->tagLatest();
				$this->changes |= self::UPDATED_GIT_TAG;

				return $this->versions->git->tag;
			} else { // They're NOT the same but we were told not to update.
				return false;
			}

		} else { // They're the same so return true
			return true;
		}
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
			if ($verbose === true) {
				switch (true) {
					case ($this->changes & self::UPDATED_GIT_TAG);
						echo "Updated git tag version to " . $this->versions->git->tag . PHP_EOL;
					case ($this->changes & self::UPDATED_SQL_DB_PATCH);
						echo "Updated Db SQL revision to " . $this->versions->sql->file . PHP_EOL;
					case ($this->changes & self::UPDATED_SQL_FILE_LAST);
						echo "Updated latest patch file to " . $this->getSQLPatchLast() . PHP_EOL;
				}
			}
			$this->xml->asXML($this->_config['path']);
			$this->changes = 0;
		}
	}

	protected function initialiseGit()
	{
		if (!($this->git instanceof \app\extensions\util\Git)) {
			$this->git = new \app\extensions\util\Git();
		}
	}

	protected function loadXMLFile()
	{
		if (empty($this->versions)) {
			$temp = libxml_use_internal_errors(true);
			$this->xml = simplexml_load_file($this->_config['path']);
			libxml_use_internal_errors($temp);

			if ($this->xml === false) {
				$this->error("Your versions XML file ($this->_config['path']) is broken, try updating from git.");
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
		parent::_init();

		if ($this->_config['git'] instanceof \app\extensions\util\Git) {
			$this->git =& $this->_config['git'];
		}
	}

	private function deprecated($methodOld, $methodUse)
	{
		trigger_error("This method ($methodOld) is deprecated. Please use '$methodUse' instead.",
			E_USER_NOTICE);
	}
}
