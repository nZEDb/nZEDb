<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace app\config\bootstrap;

//use Exception;
use lithium\action\Dispatcher;
use lithium\aop\Filters;
use lithium\core\Environment;
use lithium\core\Libraries;

/**
 * This filter intercepts the `run()` method of the `Dispatcher`, and first passes the `'request'`
 * parameter (an instance of the `Request` object) to the `Environment` class to detect which
 * environment the application is running in. Then, loads all application routes in all plugins,
 * loading the default application routes last.
 * Change this code if plugin routes must be loaded in a specific order (i.e. not the same order as
 * the plugins are added in your bootstrap configuration), or if application routes must be loaded
 * first (in which case the default catch-all routes should be removed).
 * If `Dispatcher::run()` is called multiple times in the course of a single request, change the
 * `include`s to `include_once`.
 *
 * @see lithium\action\Request
 * @see lithium\core\Environment
 * @see lithium\net\http\Router
 */
Filters::apply(Dispatcher::class, 'run', function ($params, $next) {
	Environment::set($params['request']);

	foreach (array_reverse(Libraries::get()) as $name => $config) {
		if ($name === 'lithium') {
			continue;
		}
		$file = "{$config['path']}/config/routes.php";
		file_exists($file) ? \call_user_func(function () use ($file) { include $file; }) : null;
	}

	return $next($params);
});

/**
 * This filter protects against HTTP host header attacks, by matching the `Host` header
 * sent by the client against a known list of good hostnames. You'll need to modify
 * the list of hostnames inside the filter before using it.
 *
 * @link http://li3.me/docs/book/manual/1.x/quality-code/security
 * @link http://www.skeletonscribe.net/2013/05/practical-http-host-header-attacks.html
 */
// Filters::apply(Dispatcher::class, 'run', function($params, $next) {
// 	$whitelist = [
// 		'example.org',
// 		'www.example.org'
// 	];
// 	foreach ($whitelist as $host) {
// 		if ($params['request']->host === $host) {
// 			return $next($params);
// 		}
// 	}
// 	throw new Exception('Suspicious Operation');
// });
?>
