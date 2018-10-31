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
 * not, see:.
 *
 * @link      <http://www.gnu.org/licenses/>.
 *
 * @author    niel
 * @copyright 2017 nZEDb
 */
namespace app\controllers;

use app\models\Predb;
use lithium\action\DispatchException;

class PredbController extends \lithium\action\Controller
{
	/* Comment out the actions related to views.
		public function index() {
			$predb = Predb::all();
			return compact('predb');
		}

		public function view() {
			$predb = Predb::first($this->request->id);
			return compact('predb');
		}

		public function add() {
			$predb = Predb::create();

			if (($this->request->data) && $predb->save($this->request->data)) {
				return $this->redirect(['Predb::view', 'args' => [$predb->id]]);
			}
			return compact('predb');
		}

		public function edit() {
			$predb = Predb::find($this->request->id);

			if (!$predb) {
				return $this->redirect('Predb::index');
			}
			if (($this->request->data) && $predb->save($this->request->data)) {
				return $this->redirect(['Predb::view', 'args' => [$predb->id]]);
			}
			return compact('predb');
		}

		public function delete() {
			if (!$this->request->is('post') && !$this->request->is('delete')) {
				$msg = "Predb::delete can only be called with http:post or http:delete.";
				throw new DispatchException($msg);
			}
			Predb::find($this->request->id)->delete();
			return $this->redirect('Predb::index');
		}
	*/
}

?>
