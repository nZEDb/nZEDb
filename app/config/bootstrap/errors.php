<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace app\config\bootstrap;

use lithium\action\Dispatcher;
use lithium\core\ErrorHandler;
use lithium\action\Response;
use lithium\net\http\Media;

ErrorHandler::apply(Dispatcher::class . '::run', [], 	function ($info, $params)
{
	$response = new Response([
		'request' => $params['request'],
		'status'  => $info['exception']->getCode()
	]);

	Media::render($response, compact('info', 'params'),
		[
			'library'    => true,
			'controller' => '_errors',
			'template'   => 'development',
			'layout'     => 'error',
			'request'    => $params['request']
		]
	);

	return $response;
});
?>
