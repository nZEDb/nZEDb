<?php
namespace app\controllers;

use app\models\MultigroupPosters;
use lithium\action\DispatchException;

class MultigroupPostersController extends \lithium\action\Controller
{
	public function index()
	{
		$multigroupPosters = MultigroupPosters::all();
		return compact('multigroupPosters');
	}

	public function view()
	{
		$multigroupPoster = MultigroupPosters::first($this->request->id);
		return compact('multigroupPoster');
	}

	public function add()
	{
		$multigroupPoster = MultigroupPosters::create();

		if (($this->request->data) && $multigroupPoster->save($this->request->data)) {
			return $this->redirect(['MultigroupPosters::view', 'args' => [$multigroupPoster->id]]);
		}
		return compact('multigroupPoster');
	}

	public function edit()
	{
		$multigroupPoster = MultigroupPosters::find($this->request->id);

		if (!$multigroupPoster) {
			return $this->redirect('MultigroupPosters::index');
		}
		if (($this->request->data) && $multigroupPoster->save($this->request->data)) {
			return $this->redirect(['MultigroupPosters::view', 'args' => [$multigroupPoster->id]]);
		}
		return compact('multigroupPoster');
	}

	public function delete()
	{
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = 'MultigroupPosters::delete can only be called with http:post or http:delete.';
			throw new DispatchException($msg);
		}
		MultigroupPosters::find($this->request->id)->delete();
		return $this->redirect('MultigroupPosters::index');
	}
}

?>