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
 * Class to copy the contents of one directory to another, including subdirectories.
 */
class CopyFileTree
{
	protected $_files;
	protected $_source;
	protected $_target;

	public function __construct($source, $target)
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

	public function copy($pattern = '')
	{
		if (!empty($this->_source)) {
			$this->_copyDir($pattern);
		}
	}

	public function isWIndows()
	{
		return strtolower(PHP_OS) == 'windows';
	}

	protected function _copyDir($pattern = '*')
	{
		$pattern = empty($pattern) ? '*' : $pattern;
		$filespec = $this->_source . $pattern;
		$cmd = $this->isWIndows() ? 'copy /Y' : 'cp -R';
		passthru("$cmd $filespec $this->_target", $status);
		if ($status) {
			die("Damn, something went wrong!\n");
		}
	}
}

?>
