<?php
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
