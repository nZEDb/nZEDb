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
 * @link	  <http://www.gnu.org/licenses/>.
 * @author	niel
 * @copyright 2014 nZEDb
 */
namespace app\extensions\qa\rules\syntax;

class EndsWithCloseTag extends \li3_quality\qa\rules\syntax\EndsWithCloseTag
{
	public function apply($testable, array $config = [])
	{
		$message = "File does not end with ?>";
		$lines = $testable->lines();

		$cnt = count($lines);
		if ($cnt > 1) {
			if (!((empty($lines[$cnt - 1]) && $lines[($cnt - 2)] === "?>") || ($lines[($cnt - 1)] === "?>"))) {
				$this->addViolation(
					[
						'message' => $message,
						'line' => $cnt - 1
					]);
			}
		}
	}
}

?>
