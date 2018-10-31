<?php

namespace app\controllers;

use app\models\Countries;
use lithium\action\DispatchException;

class CountriesController extends \lithium\action\Controller
{
	public function index() {
		$countries = Countries::all();
		return compact('countries');
	}

	public function view() {
		$country = Countries::first($this->request->id);
		return compact('country');
	}

	public function add() {
		$country = Countries::create();

		if (($this->request->data) && $country->save($this->request->data)) {
			return $this->redirect(['Countries::view', 'args' => [$country->id]]);
		}
		return compact('country');
	}

	public function edit() {
		$country = Countries::find($this->request->id);

		if (!$country) {
			return $this->redirect('Countries::index');
		}
		if (($this->request->data) && $country->save($this->request->data)) {
			return $this->redirect(['Countries::view', 'args' => [$country->id]]);
		}
		return compact('country');
	}

	public function delete() {
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = "Countries::delete can only be called with http:post or http:delete.";
			throw new DispatchException($msg);
		}
		Countries::find($this->request->id)->delete();
		return $this->redirect('Countries::index');
	}
}

?>
