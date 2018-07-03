<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace app\config\bootstrap;

use lithium\aop\Filters;
use lithium\console\Dispatcher;
use lithium\core\Environment;
use lithium\core\Libraries;

/**
 * This filter sets the environment based on the current request. By default, `$request->env`, for
 * example in the command `li3 help --env=production`, is used to determine the environment.
 *
 * Routes are also loaded, to facilitate URL generation from within the console environment.
 */
Filters::apply(Dispatcher::class, 'run', function ($params, $next)
{
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
 * This filter will convert {:heading} to the specified color codes. This is useful for colorizing
 * output and creating different sections.
 */
// Filters::apply(Dispatcher::class, '_call', function($params, $next) {
// 	$params['callable']->response->styles([
// 		'heading' => '\033[1;30;46m'
// 	]);
// 	return $next($params);
// });

?>
