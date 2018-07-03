<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */
namespace app\config\bootstrap;

use lithium\storage\Session;
// use lithium\security\Auth;

/**
 * This configures your session storage. The Cookie storage adapter must be connected first, since
 * it intercepts any writes where the `'expires'` key is set in the options array.
 * The default name is based on the lithium app path. Remember, if your app is numeric or has
 * special characters you might want to use Inflector::slug() or set this manually.
 */
$name = basename(LITHIUM_APP_PATH);
Session::config([
	// 'cookie' => ['adapter' => 'Cookie', 'name' => $name],
	'default' => ['adapter' => 'Php', 'session.name' => $name]
]);

/**
 * Uncomment the lines below to enable forms-based authentication. This configuration will attempt
 * to authenticate users against a `Users` model. In a controller, run
 * `Auth::check('default', $this->request)` to authenticate a user. This will check the POST data of
 * the request (`lithium\action\Request::$data`) to see if the fields match the `'fields'` key of
 * the configuration below. If successful, it will write the data returned from `Users::first()` to
 * the session using the default session configuration.
 * Once the session data is written, you can call `Auth::check('default')` to check authentication
 * status or retrieve the user's data from the session. Call `Auth::clear('default')` to remove the
 * user's authentication details from the session. This effectively logs a user out of the system.
 * To modify the form input that the adapter accepts, or how the configured model is queried, or how
 * the data is stored in the session, see the `Form` adapter API or the `Auth` API, respectively.
 *
 * @see lithium\security\auth\adapter\Form
 * @see lithium\action\Request::$data
 * @see lithium\security\Auth
 */
// Auth::config([
// 	'default' => [
// 		'adapter' => 'Form',
// 		'model' => 'Users',
// 		'fields' => ['username', 'password']
// 	]
// ]);

use app\models\Users;
use lithium\security\Password;

Auth::config(
	[
		'default' => [
			'adapter' => 'Form',
			'model'   => 'Users'
		],
	]
);
/*
if (!\lithium\data\Connections::get('default')) {
	Users::applyFilter('save',
		function ($self, $params, $chain)
		{
			if ($params['data']) {
				$params['entity']->set($params['data']);
				$params['data'] = [];
			}

			if (!$params['entity']->exists()) {
				$params['entity']->password = Password::hash($params['entity']->password);
			}

			return $chain->next($self, $params, $chain);
		}
	);
}
*/
?>
