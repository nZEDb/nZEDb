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

use \app\models\Settings;

class Versions extends \lithium\core\Object
{
	/**
	 * These constants are bitwise for checking what was changed.
	 */
	const UPDATED_GIT_COMMIT	= 1;
	const UPDATED_GIT_TAG		= 2;
	const UPDATED_SQL_DB_PATCH	= 4;
	const UPDATED_SQL_FILE_LAST	= 8;

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

	public function checkGitTag($update = true)
	{
		$this->checkGitTagInFile();
		return $this->checkGitTagsAreEqual(false);
	}

	/**
	 * Checks the git's latest version tag against the XML's stored value. Version should be
	 * Major.Minor.Revision[.fix][-dev|-RCx]
	 *
	 * @return string|false version string if matched or false.
	 */
	public function checkGitTagInFile()
	{
		$this->initialiseGit();
		$ver = preg_match('#v(\d+\.\d+\.\d+(?:\.\d+)?).*#', $this->git->tagLatest(), $matches) ?
			$matches[1] : $this->git->tagLatest();
		$ver = ($ver == false) ? false : $ver; // Convert '0' to false.

		if ($ver !== false) {
			if (!in_array($this->git->getBranch, $this->git->getBranchesStable)) {
				if (version_compare($this->versions->git->tag, '0.0.0', '!=')) {
					$this->versions->git->tag = '0.0.0-dev';
					$this->changes |= self::UPDATED_GIT_TAG;
				}

				$ver = $this->versions->git->tag;
			}
		}

		return $ver;
	}

	public function checkGitTagsAreEqual($update = true)
	{
		// Check if file's entry is the same as current branch's tag
		if (version_compare($this->versions->git->tag, $this->git->tagLatest(), '!=')) {
			if ($update === true) {
				//$this->out->primaryOver("Updating tag version to ") . $this->out->header($this->git->tagLatest());

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

	public function save()
	{
		if ($this->hasChanged()) {
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
			$versions = $this->_config['path'];

			$temp = libxml_use_internal_errors(true);
			$this->xml = simplexml_load_file($versions);
			libxml_use_internal_errors($temp);

			if ($this->xml === false) {
				$this->error("Your versions XML file ($versions) is broken, try updating from git.");
				throw new \Exception("Failed to open versions XML file '$versions'");
			}

			if ($this->xml->count() > 0) {
				$vers = $this->versions->xpath('/nzedb/versions');

				if ($vers[0]->count() == 0) {
					$this->error("Your versions XML file ($versions) does not contain version info, try updating from git.");
					throw new \Exception("Failed to find versions node in XML file '$versions'");
				} else {
					$this->versions = &$this->xml->versions;
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
