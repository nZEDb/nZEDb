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
 * @copyright 2016 nZEDb
 */

namespace app\controllers;

use app\models\Settings;
use lithium\action\DispatchException;

class SettingsController extends \lithium\action\Controller {

	public function index() {
		$settings = Settings::all();
		return compact('settings');
	}

	public function view() {
		$setting = Settings::first($this->request->id);
		return compact('setting');
	}

	public function add() {
		$setting = Settings::create();

		if (($this->request->data) && $setting->save($this->request->data)) {
			return $this->redirect(array('Settings::view', 'args' => array($setting->id)));
		}
		return compact('setting');
	}

	public function edit() {
		$setting = Settings::find($this->request->id);

		if (!$setting) {
			return $this->redirect('Settings::index');
		}
		if (($this->request->data) && $setting->save($this->request->data)) {
			return $this->redirect(array('Settings::view', 'args' => array($setting->id)));
		}
		return compact('setting');
	}

	public function delete() {
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = "Settings::delete can only be called with http:post or http:delete.";
			throw new DispatchException($msg);
		}
		Settings::find($this->request->id)->delete();
		return $this->redirect('Settings::index');
	}
}

?>
