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
 * @copyright 2019 nZEDb
 */

namespace zed\controllers;


use Cake\ORM\TableRegistry;

trait TableObjectTrait
{
	public function initialiseTableObject(): void
	{
		$class = explode('\\', get_class($this));
		// TODO Repleace `end($class)` with `array_key_last(explode(...))` when PHP 7.3 is our minimum version
		$this->table = TableRegistry::getTableLocator()->get(end($class));
	}
}

?>
