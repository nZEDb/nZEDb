<?php

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
