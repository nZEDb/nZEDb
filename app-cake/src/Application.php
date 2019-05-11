<?php
namespace App;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function bootstrap(): void
	{
		// Call parent to load bootstrap from files.
		parent::bootstrap();

		$this->addPlugin('Migrations');

		$this->addPlugin('Authentication');

		if (PHP_SAPI === 'cli') {
			try {
				$this->addPlugin('Bake');
			} catch (MissingPluginException $e) {
				// Do not halt if the plugin is missing
			}

			$this->addPlugin('Migrations');
		}

		/*
		 * Only try to load DebugKit in development mode
		 * Debug Kit should not be installed on a production system
		 */
		if (Configure::read('debug')) {
		   $this->addPlugin(\DebugKit\Plugin::class);
		}
	}

	/**
	 * Returns a service provider instance.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request  Request
	 * @param \Psr\Http\Message\ResponseInterface	  $response Response
	 *
	 * @return \Authentication\AuthenticationServiceInterface
	 */
	public function getAuthenticationService(
		ServerRequestInterface $request,
		ResponseInterface $response): AuthenticationServiceInterface
	{
		$service = new AuthenticationService();

		$fields = [
			'username' => 'username',
			'password' => 'password'
		];

		// Load identifiers
		$service->loadIdentifier('Authentication.Password', compact('fields'));

		// Load the authenticators, you want session first
		$service->loadAuthenticator('Authentication.Session');
		$service->loadAuthenticator('Authentication.Form',
			[
				'fields'   => $fields,
				'loginUrl' => '/users/login'
			]);

		return $service;
	}

	/**
	 * Setup the middleware queue your application will use.
	 *
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
	 * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
	 */
	public function middleware($middlewareQueue): MiddlewareQueue
	{
		$middlewareQueue
			// Catch any exceptions in the lower layers, and make an error page/response
			->add(new ErrorHandlerMiddleware(null, Configure::read('Error')))

			// Handle plugin/theme assets like CakePHP normally does.
			->add(new AssetMiddleware([
				'cacheTime' => Configure::read('Asset.cacheTime')
			]))

			// Add routing middleware.
			// Routes collection cache enabled by default, to disable route caching
			// pass null as cacheConfig, example: `new RoutingMiddleware($this)`
			// you might want to disable this cache in case your routing is extremely simple
			->add(new RoutingMiddleware($this, '_cake_routes_'));

		// Add the authentication middleware to the middleware queue
		$middlewareQueue->add(new AuthenticationMiddleware($this),
			[
				'unauthenticatedRedirect'	=> Router::url('users:login'),
				'queryParam'				=> 'redirect',
			]
		);


		return $middlewareQueue;
	}
}
