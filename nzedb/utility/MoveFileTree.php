<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\utility;

/**
 * Class to move the contents of one directory to another, including subdirectories.
 */
class MoveFileTree
{
	protected $_dirs;
	protected $_files;
	protected $_source;
	protected $_target;

	private $rItIt;

	/**
	 *
	 *
	 * @param type $source A filepath to a valid directory, or an array of valid
	 *                     filepaths (not neccessarily in the same directory).
	 * @param type $target A valid directory that the files and directories will
	 *                     be moved to.
	 * @param bool $moveSourceBase
	 *
	 * @throws \UnexpectedValueException
	 *
	 */
	function __construct($source, $target, $moveSourceBase = true)
	{
		if (empty($source)) {
			throw  new \UnexpectedValueException('Source value cannot be empty! Source is not a path to a directory.');
		} else if (!is_array($source) && file_exists($source) && is_dir($source)) {
			$contents = scandir($source);
			if (count($contents) < 3) {
				throw new \UnexpectedValueException('Source directory does not contain anything to move.');
			}
			$source = substr($source, -1) == DS ? $source : $source . DS;
			$this->_source = realpath($source);
		} else {
			throw new \UnexpectedValueException("Source value is required! It must be a path to an existing directory\nSource: $source\n");
		}

		if (!empty($target) && file_exists($target) && is_dir($target)) {
			$target2 = substr($target, -1) == DS ? $target : $target . DS;
			$target2 = $target2 . basename($source);
			if (!file_exists($target2) && $moveSourceBase) {
				if (mkdir($target2)) {
					$this->_target = realpath($target2);
				} else {
					throw new \RuntimeException("Could not create '$target2' directory");
				}
			} else {
				$this->_target = realpath($target);
			}
		} else {
			throw new \UnexpectedValueException("Target value is required, it must be be a valid path to a directory\nTarget: $target\n");
		}
	}

	public function clearEmpty()
	{
		if (empty($this->_dirs)) {
			if (empty($this->_source)) {
				return;
			}
			$this->_enumerateSource($this->_source);
		}

		if (empty($this->_dirs)) {
			return;
		}
		rsort($this->_dirs); // Sort in reverse order so deleting will remove the deepest first

		foreach ($this->_dirs as $dir) {
			echo "Checking '$dir' ";
			$contents = scandir($dir);
			if (count($contents) == 2) {
				echo "is empty - ";
				if (rmdir($dir)) {
					echo "deleting!\n";
				} else {
					echo "failed!\n";
				}
			} else {
				echo "has " . (count($contents) - 2) . " files in it\n";
			}
		}
	}

	public function move($pattern = '')
	{
		if (!empty($this->_source)) {
			$this->_moveDir($pattern);
			$this->_enumerateSource($this->_source);
		}
		$this->_moveFiles();
	}

	public function isWIndows()
	{
		return strtolower(PHP_OS) == 'windows';
	}

	protected function _enumerateSource($filespec)
	{
		$this->_dirs = $this->_files = $files = $dirs = [];
		$this->rItIt = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$filespec,
				\FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::UNIX_PATHS
				)
			);

		while ($this->rItIt->valid()) {
			if (!$this->rItIt->isDot()) {
				$files[] = $this->rItIt->key();
			} else {
				if (preg_match('#^(.+)[^.]\.{1,2}$#', $this->rItIt->key(), $match)) {
					$dirs[] = $match[1];
				}
			}
			$this->rItIt->next();
		}
		$dirs = array_unique($dirs);
		$this->_dirs = $dirs;
		$this->_files = $files;
	}

	protected function _moveDir($pattern = '')
	{
		$pattern = empty($pattern) ? '*' : $pattern;
		$filespec = $this->_source . $pattern;
		$cmd = $this->isWIndows() ? 'move /Y' : 'mv';
		passthru("$cmd $filespec $this->_target", $status);
		if ($status) {
			die("Damn, something went wrong!\n");
		}

		$this->_enumerateSource($this->_source);
	}

	protected function _moveFiles()
	{
		if (count($this->_files)) {
			$this->rItIt->rewind();
			while ($this->rItIt->valid()) {
				if (!$this->rItIt->isDot()) {
					echo "Copying to: $this->_target{$this->rItIt->getSubPathName()} ";
					if (copy($this->rItIt->key(), $this->_target . $this->rItIt->getSubPathName())) {
						echo "Done\n";
						@unlink($this->rItIt->key());
					} else {
						echo "Failed!\n";
					}
				}
				$this->rItIt->next();
			}
		}
	}
}

?>
