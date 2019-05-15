<?php
namespace App\View;

use Cake\View\View;

/**
 * Application View
 *
 * Your application’s default view class
 *
 * @link https://book.cakephp.org/3.0/en/views.html#the-app-view
 */
class AppView extends View
{
	/**
	 * @var
	 */
	public $Identity;

	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading helpers.
	 *
	 * e.g. `$this->loadHelper('Html');`
	 *
	 * @return void
	 */
	public function initialize()
	{
		$this->loadHelper('Form', ['className' => 'AdminLTE.Form']);

		$this->loadHelper('Authentication.Identity');
	}
}
