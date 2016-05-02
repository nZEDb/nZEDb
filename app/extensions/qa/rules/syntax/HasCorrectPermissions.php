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
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2015 nZEDb
 */
namespace app\extensions\qa\rules\syntax;

class HasCorrectPermissions extends \li3_quality\qa\rules\syntax\HasCorrectPermissions
{
	/**
	 * @var array Specific permissions allowed for specified file suffixes.
	 */
	public $permsAllowed = [
		'php' => '775',
	];

	/**
	 * @var string Default permissions for unspecified file suffixes.
	 */
	public $permsDefault = '644';

	public function apply($testable, array $config = [])
	{
		$suffix = pathinfo($testable->config('path'), PATHINFO_EXTENSION);
		$message = "Permissions for '.%s' files should be '%s', found '%s'.";
		$permsFound = substr(sprintf('%o', fileperms($testable->config('path'))), -3);
		$permsAllowed = array_key_exists($suffix, $this->permsAllowed)
				 ? $this->permsAllowed[$suffix] : $this->permsDefault;

		if (!preg_match("#$permsAllowed#", $permsFound)) {
			$this->addViolation([
				'message' => sprintf(
					$message,
					$suffix,
					$permsAllowed,
					$permsFound
				)
			]);
		}
	}
}

?>
