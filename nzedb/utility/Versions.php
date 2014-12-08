<?php
namespace nzedb\utility;

if (!defined('GIT_PRE_COMMIT')) {
	define('GIT_PRE_COMMIT', false);
}

class Versions
{
	/**
	 * These constants are bitwise for checking what was changed.
	 */
	const UPDATED_GIT_COMMIT	= 1;
	const UPDATED_GIT_TAG		= 2;
	const UPDATED_SQL_DB_PATCH	= 4;
	const UPDATED_SQL_FILE_LAST	= 8;

	/**
	 * @var \nzedb\utility\Git instance variable.
	 */
	public $git;

	/**
	 * @var object ColorCLI
	 */
	public $out;

	/**
	 * @var integer Bitwise mask of elements that have been changed.
	 */
	protected $_changes = 0;

	/**
	 * @var string	Path and filename for the XML file.
	 */
	protected $_filespec;

	/**
	 * Shortcut to the nzedb->versions node to make method work shorter.
	 * @var object SimpleXMLElement
	 */
	protected $_vers;

	/**
	 * @var object simpleXMLElement
	 */
	protected $_xml;

	/**
	 * Class constructor initialises the SimpleXML object and sets a few properties.
	 * @param string $filepath Optional filespec for the XML file to use. Will use default otherwise.
	 *
	 * @throws \Exception If the XML is invalid.
	 * @throws \RuntimeException If version file does not exist.
	 */
	public function __construct($filepath = null)
	{
		if (empty($filepath)) {
			if (defined('nZEDb_VERSIONS')) {
				$filepath = nZEDb_VERSIONS;
			}
		}

		if (!file_exists($filepath)) {
			throw new \RuntimeException("Versions file '$filepath' does not exist!'");
		}
		$this->_filespec = $filepath;

		$this->out = new \ColorCLI();
		$this->git = new Git();

		$this->getValidVersionsFile();
	}

	public function changes()
	{
		return $this->_changes;
	}

	/**
	 * Run all checks
	 * @param boolean $update Whether the XML should be updated by the check.
	 * @return boolean	True if any of the checks actually caused an update (not if it indicated one was needed), flase otherwise
	 */
	public function checkAll($update = true)
	{
		$this->checkGitTag($update);
		//$this->checkSQLFileLatest($update);
		$this->checkSQLDb($update);
		$this->checkGitCommit($update);
		return $this->hasChanged();
	}

	/**
	 * Checks the git commit number against the XML's stored value.
	 * @param boolean $update Whether the XML should be updated by the check.
	 * @return integer|boolean The new git commit number, or false.
	 */
	public function checkGitCommit($update = true)
	{
		// Since Dec 2014 we no longer maintain the git commit count in the XML file, as it is no
		// longer used in the code base.
		if ((int)$this->_vers->sql->db >= 307) {
			return 0;
		}

		$count = $this->git->commits();
		if ($this->_vers->git->commit->__toString() < $count || GIT_PRE_COMMIT === true) {	// Allow pre-commit to override the commit number (often branch number is higher than dev's)
			if ($update) {
				if (GIT_PRE_COMMIT === true) { // only the pre-commit script is allowed to set the NEXT commit number
					$count += 1;
				}
				if ($count != $this->_vers->git->commit) {
					echo $this->out->primary("Updating commit number to {$count}");
					$this->_vers->git->commit = $count;
					$this->_changes |= self::UPDATED_GIT_COMMIT;
				}
			}
			return $this->_vers->git->commit;
		}
		return false;
	}

	/**
	 * Checks the git's latest version tag against the XML's stored value. Version should be
	 * Major.Minor.Revision (Note commit number is NOT revision)
	 * @param boolean $update Whether the XML should be updated by the check.
	 * @return boolean The new git's latest version tag, or false.
	 */
	public function checkGitTag($update = true)
	{
		$latest = $this->git->tagLatest();
		$ver = preg_match('#v(\d+\.\d+\.\d+).*#', $latest, $matches) ? $matches[1] : $latest;

		// Check if version file's entry is the same as current branch's tag
		if (version_compare($this->_vers->git->tag, $latest, '!=')) {
			if ($update) {
				echo $this->out->primaryOver("Updating tag version to ") . $this->out->headerOver($latest);
				$this->_vers->git->tag = $ver;
				$this->_changes |= self::UPDATED_GIT_TAG;
			} else {
				echo $this->out->primaryOver("Leaving tag version at ") .
					 $this->out->headerOver($this->_vers->git->tag);
			}
			return $this->_vers->git->tag;
		} else {
			echo $this->out->primaryOver("Tag version is ") . $this->out->header($latest);
		}
		return false;
	}

	/**
	 * Checks the database sqlpatch setting against the XML's stored value.
	 *
	 * @param boolean $update Whether the XML should be updated by the check.
	 *
	 * @return boolean The new database sqlpatch version, or false.
	 */
	public function checkSQLDb($update = false)
	{
		$this->checkSQLFileLatest($update);

		//$settings = new Settings();
		//$setting  = $settings->getSetting('sqlpatch');

		if ($this->_vers->sql->db->__toString() != $this->_vers->sql->file->__toString()) {
			if ($update) {
				echo $this->out->primaryOver("Updating Db revision to " . $this->_vers->sql->file);
				$this->_vers->sql->db = $this->_vers->sql->file->__toString();
				$this->_changes |= self::UPDATED_SQL_DB_PATCH;
			}
			return $this->_vers->patch->db;
		}
		return false;
	}

	/**
	 * Checks the numeric value from the last SQL patch file, updating the versions file if desired.
	 *
	 * @param bool $update	Whether to update the versions file.
	 *
	 * @return bool|int	False if there is a problem, otherwise the number from the last patch file.
	 */
	public function checkSQLFileLatest($update = true)
	{
		$options = [
			'data'  => nZEDb_RES . 'db' . DS . 'schema' . DS . 'data' . DS,
			'ext'   => 'sql',
			'path'  => nZEDb_RES . 'db' . DS . 'patches' . DS . 'mysql',
			'regex' =>
				'#^' . Utility::PATH_REGEX . '(?P<patch>\d{4})~(?P<table>\w+)\.sql$#',
			'safe'  => true,
		];
		$files = Utility::getDirFiles($options);
		natsort($files);

		$last = (preg_match($options['regex'], end($files), $matches)) ? (int)$matches['patch'] : false;

		if ($update) {
			if ($last !== false && $this->_vers->sql->file->__toString() != $last) {
				echo $this->out->primary("Updating latest patch file to " . $last);
				$this->_vers->sql->file = $last;
				$this->_changes |= self::UPDATED_SQL_FILE_LAST;
			}

			if ($this->_vers->sql->file->__toString() != $last) {
				$this->_vers->sql->file = $last;
				$this->_changes |= self::UPDATED_SQL_DB_PATCH;
			}
		}
		return $last;
	}

	public function getCommit()
	{
		return $this->_vers->git->commit->__toString();
	}

	public function getGitHookPrecommit()
	{
		return $this->_vers->git->hooks->precommit->__toString();
	}

	public function getSQLPatchFromDb()
	{
		return $this->_vers->sql->db->__toString();
	}

	public function getSQLPatchFromFiles()
	{
		return $this->_vers->sql->file->__toString();
	}

	public function getTagVersion()
	{
		return $this->_vers->git->tag->__toString();
	}

	public function getValidVersionsFile($filepath = null)
	{
		$filepath = empty($filepath) ? $this->_filespec : $filepath;

		$temp = libxml_use_internal_errors(true);
		$this->_xml = simplexml_load_file($filepath);
		libxml_use_internal_errors($temp);

		if ($this->_xml === false) {
			if (Utility::isCLI()) {
				$this->out->error("Your versions XML file ($filepath) is broken, try updating from git.");
			}
			throw new \Exception("Failed to open versions XML file '$filepath'");
		}

		if ($this->_xml->count() > 0) {
			$vers = $this->_xml->xpath('/nzedb/versions');

			if ($vers[0]->count() == 0) {
				$this->out->error("Your versions XML file ({nZEDb_VERSIONS}) does not contain version info, try updating from git.");
				throw new \Exception("Failed to find versions node in XML file '$filepath'");
			} else {
				$this->out->primary("Your versions XML file ({nZEDb_VERSIONS}) looks okay, continuing.");
				$this->_vers = &$this->_xml->versions;
			}
		} else {
			throw new \RuntimeException("No elements in file!\n");
		}

		return $this->_xml;
	}

	/**
	 * Check whether the XML has been changed by one of the methods here.
	 * @return boolean True if the XML has been changed.
	 */
	public function hasChanged()
	{
		return $this->_changes != 0;
	}

	public function save()
	{
		if ($this->hasChanged()) {
			$this->_xml->asXML($this->_filespec);
			$this->_changes = 0;
		}
	}
}
?>
