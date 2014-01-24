<?php
require_once dirname(__FILE__) . '/../www/config.php';

if (PHP_SAPI == 'cli') {
	$vers = new UpdateVersions();
	$vers->checkAll();
	$vers->save();
}

class UpdateVersions
{
	/**
	 * These constants are bitwise for checking what was changed.
	 */
	const UPDATED_DB_REVISION	= 1;
	const UPDATED_GIT_COMMIT	= 2;
	const UPDATED_GIT_TAG		= 4;

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
	 * @var object Sites/Settings
	 */
	protected $_settings;

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
	 * @throws Exception If the XML is invalid.
	 */
	public function __construct($filepath = null)
	{
		if (empty($filepath)) {
			$filepath = nZEDb_VERSIONS;
		}
		$this->_filespec = $filepath;

		$this->out = new ColorCLI();
		$this->_xml = @new SimpleXMLElement($filepath, 0, true);
		if ($this->_xml === false) {
			$this->out->error("Your versioning XML file ({nZEDb_VERSIONS}) is broken, try updating from git.");
			throw new Exception("Failed to open versions XML file '$filename'");
		}

		if ($this->_xml->count() > 0) {
			$vers = $this->_xml->xpath('/nzedb/versions');

			if ($vers[0]->count() == 0) {
				$this->out->error("Your versioning XML file ({nZEDb_VERSIONS}) does not contain versioning info, try updating from git.");
				throw new Exception("Failed to find versions node in XML file '$filename'");
			} else {
				$this->out->primary("Your versioning XML file ({nZEDb_VERSIONS}) looks okay, continuing.");
				$this->_vers = &$this->_xml->versions;
			}
		} else {
			exit("No elements in file!\n");
		}

		$s = new Sites();
		$this->_settings = $s->get();
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
		$this->checkDb($update);
		$this->checkGitCommit($update);
		$this->checkGitTag($update);
		return $this->hasChanged();
	}

	/**
	 * Checks the database sqlpatch setting against the XML's stored value.
	 * @param boolean $update Whether the XML should be updated by the check.
	 * @return boolean True if the database sqlpatch version is more than that of XML element (.i.e. needs updating).
	 */
	public function checkDb($update = true)
	{
		if ($this->_vers->db < $this->_settings->sqlpatch) {
			if ($update) {
				echo $this->out->primary("Updating Db revision to " . $this->_settings->sqlpatch);
				$this->_vers->db = $this->_settings->sqlpatch;
				$this->_changes |= self::UPDATED_DB_REVISION;
			}
			return true;
		}
		return false;
	}

	/**
	 * Checks the git commit number against the XML's stored value.
	 * @param boolean $update Whether the XML should be updated by the check.
	 * @return boolean True if the dgit commit number is more than that of XML element (.i.e. needs updating).
	 */
	public function checkGitCommit($update = true)
	{
		exec('git log | grep "^commit" | wc -l', $output);
		if ($this->_vers->git->commit < $output[0]) {
			if ($update) {
				$output[0] += 1;
				echo $this->out->primary("Updating commit number to {$output[0]}");
				$this->_vers->git->commit = $output[0];
				$this->_changes |= self::UPDATED_GIT_COMMIT;
			}
			return true;
		}
		return false;
	}

	/**
	 * Checks the git's latest version tag against the XML's stored value. Version should be
	 * Major.Minor.Revision (Note commit number is NOT revision)
	 * @param boolean $update Whether the XML should be updated by the check.
	 * @return boolean True if the git's latest version tag is higher than that of XML element (.i.e. needs updating).
	 */
	public function checkGitTag($update = true)
	{
		exec('git log --tags', $output);
		$index = 0;
		$count = count($output);
		while (!preg_match('#v(\d+\.\d+\.\d+)#i', $output[$index], $match) && $count < $index ) {
			$index++;
		}

		// TODO this needs a better test. Think PHP has a way to do this, will update later.
		if (!empty($match) && $this->_vers->git->tag < $match) {
			if ($update) {
				echo $this->out->primary("Updating tag version to $match");
				$this->_vers->git->tag = $match;
				$this->_changes |= self::UPDATED_GIT_TAG;
			}
			return true;
		}
		return false;
	}
/*
	public function check($update = true)
	{
		if ($this->vers->setting) {
			if ($update) {
				echo $this->out->primary("\n");
				;
				$this->_updated = true;
			}
			return true;
		}
		return false;
	}
 */

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
