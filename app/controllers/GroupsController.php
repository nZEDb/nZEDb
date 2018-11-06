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
 * @copyright 2018 nZEDb
 */
namespace app\controllers;


use app\models\Groups;
use lithium\action\DispatchException;


class GroupsController extends \lithium\action\Controller
{
	public function add()
	{
		$group = Groups::create();

		if ($this->request->data && $group->save($this->request->data)) {
			return $this->redirect(['Groups::view', 'args' => [$group->id]]);
		}

		return \compact('group');
	}

	public function delete()
	{
		if (! $this->request->is('post') && ! $this->request->is('delete')) {
			$msg = 'Groups::delete can only be called with http:post or http:delete.';

			throw new DispatchException($msg);
		}
		Groups::find($this->request->id)->delete();

		return $this->redirect('Groups::index');
	}

	public function edit()
	{
		$group = Groups::find($this->request->id);

		if (! $group) {
			return $this->redirect('Groups::index');
		}
		if ($this->request->data && $group->save($this->request->data)) {
			return $this->redirect(['Groups::view', 'args' => [$group->id]]);
		}

		return \compact('group');
	}

	public function index()
	{
		$groups = Groups::all();

		return \compact('groups');
	}

	public function view()
	{
		$group = Groups::first($this->request->id);

		return \compact('group');
	}
}

?>
