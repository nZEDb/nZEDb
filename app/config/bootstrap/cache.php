<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace app\config\bootstrap;

use lithium\action\Dispatcher;
use lithium\aop\Filters;
use lithium\storage\Cache;
use lithium\core\Libraries;
use lithium\core\Environment;
use lithium\data\source\database\adapter\MySql;
use lithium\data\source\database\adapter\PostgreSql;
use lithium\data\source\database\adapter\Sqlite3;

/**
 * Configuration
 *
 * Configures the adapters to use with the cache class. Available adapters are `Memcache`,
 * `File`, `Redis`, `Apc`, `XCache` and `Memory`. Please see the documentation on the
 * adapters for specific characteristics and requirements.
 *
 * By default the almost always available `File` cache adapter is used below. This
 * is for getting oyu up and running only and should be replaced with a better Cache
 * configuration, based on the cache/s you plan to use.
 *
 * @see lithium\storage\Cache
 * @see lithium\storage\cache\adapters
 * @see lithium\storage\cache\strategies
 */
Cache::config([
	'default' => [
		'adapter'    => 'File',
		'strategies' => ['Serializer']
	]
]);
// Cache::config([
// 	'default' => [
// 		'adapter' => 'Memcache',
// 		'scope' => hash('md5', LITHIUM_APP_PATH)
// 	]
// ]);

/**
 * Apply
 *
 * Applies caching to neuralgic points of the framework but only when we are running
 * in production. This is also a good central place to add your own caching rules.
 * A couple of caching rules are already defined below:
 *
 *  1. Cache paths for auto-loaded and service-located classes.
 *  2. Cache describe calls on all connections that use a `Database` based adapter.
 *
 * @see lithium\core\Environment
 * @see lithium\core\Libraries
 */
if (!Environment::is('production')) {
	return;
}

Filters::apply(Dispatcher::class, 'run', function ($params, $next) {
	$cacheKey = 'core.libraries';

	if ($cached = Cache::read('default', $cacheKey)) {
		$cached = (array)$cached + Libraries::cache();
		Libraries::cache($cached);
	}
	$result = $next($params);

	if ($cached != ($data = Libraries::cache())) {
		Cache::write('default', $cacheKey, $data, '+1 day');
	}

	return $result;
});

$schemaCache = function ($params, $next) {
	if ($params['fields']) {
		return $next($params);
	}
	$cacheKey = "data.connections.{$params['meta']['connection']}.";
	$cacheKey .= "sources.{$params['entity']}.schema";

	return Cache::read('default',
		$cacheKey,
		[
			'write' => function () use ($params, $next) {
				return ['+1 day' => $next($params)];
			}
		]);
};

Filters::apply(MySql::class, 'describe', $schemaCache);
Filters::apply(PostgreSql::class, 'describe', $schemaCache);
Filters::apply(Sqlite3::class, 'describe', $schemaCache);

?>
