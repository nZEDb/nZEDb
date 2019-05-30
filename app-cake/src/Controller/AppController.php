<?php
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;


/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
	/**
	 * @var \Authentication\AuthenticationServiceInterface
	 */
	public $Authentication;

	/**
	 * @var \Authorization\Controller\Component\AuthorizationComponent
	 */
	public $Authorization;

	/**
	 * @var \Authentication\Identity
	 */
	protected $identity;


	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading components.
	 *
	 * e.g. `$this->loadComponent('Security');`
	 *
	 * @return void
	 */
	public function initialize(): void
	{
		parent::initialize();

		$this->loadComponent('RequestHandler', [
			'enableBeforeRedirect' => false,
		]);

		$this->loadComponent('Flash');

		$this->loadComponent('Authentication.Authentication', [
			'logoutRedirect' => '/users/login'  // Default is false
		]);
/*
		$this->loadComponent('Authorization.Authorization', [
			'skipAuthorization' => [
				'display',
				'join',
				'login',
				'logout',
				'register'
			]
		]);
*/
		$this->identity = $this->request->getAttribute('identity');


		/*
		 * Enable the following component for recommended CakePHP security settings.
		 * see https://book.cakephp.org/3.0/en/controllers/components/security.html
		 */
		//$this->loadComponent('Security');
	}
}
