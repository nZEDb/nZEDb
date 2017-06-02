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
 * @copyright 2017 nZEDb
 */

namespace app\controllers;

use app\models\MenuItems;
use lithium\action\DispatchException;

class MenuItemsController extends \app\extensions\action\Controller
{
	public function index()
	{
		$menuItems = MenuItems::all();
		return compact('menuItems');
	}

	public function view()
	{
		$menuItem = MenuItems::first($this->request->id);
		return compact('menuItem');
	}

	public function add()
	{
		$menuItem = MenuItems::create();

		if (($this->request->data) && $menuItem->save($this->request->data)) {
			return $this->redirect(['MenuItems::view', 'args' => [$menuItem->id]]);
		}
		return compact('menuItem');
	}

	public function edit()
	{
		$menuItem = MenuItems::find($this->request->id);

		if (!$menuItem) {
			return $this->redirect('MenuItems::index');
		}
		if (($this->request->data) && $menuItem->save($this->request->data)) {
			return $this->redirect(['MenuItems::view', 'args' => [$menuItem->id]]);
		}
		return compact('menuItem');
	}

	public function delete()
	{
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = "MenuItems::delete can only be called with http:post or http:delete.";
			throw new DispatchException($msg);
		}
		MenuItems::find($this->request->id)->delete();
		return $this->redirect('MenuItems::index');
	}

	public function import(array $options)
	{
		return parent::import($options, MenuItems::connection());
	}
}

?>
