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


use app\models\Videos;
use lithium\action\DispatchException;


class VideosController extends \lithium\action\Controller {

	public function index() {
		$videos = Videos::all();
		return compact('videos');
	}

	public function view() {
		$video = Videos::first($this->request->id);
		return compact('video');
	}

	public function add() {
		$video = Videos::create();

		if ($this->request->data && $video->save($this->request->data)) {
			return $this->redirect(['Videos::view', 'args' => [$video->id]]);
		}
		return compact('video');
	}

	public function edit() {
		$video = Videos::find($this->request->id);

		if (!$video) {
			return $this->redirect('Videos::index');
		}
		if ($this->request->data && $video->save($this->request->data)) {
			return $this->redirect(['Videos::view', 'args' => [$video->id]]);
		}
		return compact('video');
	}

	public function delete() {
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = 'Videos::delete can only be called with http:post or http:delete.';
			throw new DispatchException($msg);
		}
		Videos::find($this->request->id)->delete();
		return $this->redirect('Videos::index');
	}
}

?>
