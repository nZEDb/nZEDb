<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\storage\Cache;
use lithium\storage\cache\adapter\Apc;
use lithium\core\Libraries;
use lithium\core\Environment;
use lithium\action\Dispatcher;
use lithium\data\Connections;
use lithium\data\source\Database;

/**
 * Configuration
 *
 * Configures the adapters to use with the cache class. Available adapters are `Memcache`,
 * `File`, `Redis`, `Apc`, `XCache` and `Memory`. Please see the documentation on the
 * adapters for specific characteristics and requirements.
 *
 * Most of this code is for getting you up and running only, and should be replaced with
 * a hard-coded configuration, based on the cache(s) you plan to use.
 *
 * We create a default cache configuration using the most optimized adapter available, and
 * use it to provide default caching for high-overhead operations. If APC is not available
 * and we can't degrade to file based caching, bail out.
 *
 * @see lithium\storage\Cache
 * @see lithium\storage\cache\adapters
 * @see lithium\storage\cache\strategies
 */
$cachePath = Libraries::get(true, 'resources') . '/tmp/cache';

if (!(($apc = Apc::enabled()) || PHP_SAPI === 'cli') && !is_writable($cachePath)) {
	return;
}
Cache::config(array(
	'default' => array(
		'adapter' => $apc ? 'Apc' : 'File',
		'strategies' => $apc ? array() : array('Serializer'),
		'scope' => $apc ? md5(LITHIUM_APP_PATH) : null
	)
));

/**
 * Apply
 *
 * Applies caching to neuralgic points of the framework but only when we are running
 * in production. This is also a good central place to add your own caching rules.
 *
 * A couple of caching rules are already defined below:
 *  1. Cache paths for auto-loaded and service-located classes.
 *  2. Cache describe calls on all connections that use a `Database` based adapter.
 *
 * @see lithium\core\Environment
 * @see lithium\core\Libraries
 */
if (!Environment::is('production')) {
	return;
}

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	$cacheKey = 'core.libraries';

	if ($cached = Cache::read('default', $cacheKey)) {
		$cached = (array) $cached + Libraries::cache();
		Libraries::cache($cached);
	}
	$result = $chain->next($self, $params, $chain);

	if ($cached != ($data = Libraries::cache())) {
		Cache::write('default', $cacheKey, $data, '+1 day');
	}
	return $result;
});

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	foreach (Connections::get() as $name) {
		if (!(($connection = Connections::get($name)) instanceof Database)) {
			continue;
		}
		$connection->applyFilter('describe', function($self, $params, $chain) use ($name) {
			if ($params['fields']) {
				return $chain->next($self, $params, $chain);
			}
			$cacheKey = "data.connections.{$name}.sources.{$params['entity']}.schema";

			return Cache::read('default', $cacheKey, array(
				'write' => function() use ($self, $params, $chain) {
					return array('+1 day' => $chain->next($self, $params, $chain));
				}
			));
		});
	}
	return $chain->next($self, $params, $chain);
});

?>