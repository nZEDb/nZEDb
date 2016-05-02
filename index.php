<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * This file may act as a forwarder for the actual front-controller within `app/webroot/index.php`.
 * This is to make setups work where you have placed the whole project within the webserver's
 * docroot. This should be avoided in production environments. Instead point the webserver's
 * docroot to `app/webroot` and let just that be served. You may then safely remove this file.
 *
 * This file may also act as a router script for the built-in PHP **development** webserver. Files
 * present inside of the webroot (i.e. CSS) are served directly. To start the webserver execute
 * the following command:
 *
 * ```
 * php -S 127.0.0.1:8080 -t app/webroot index.php
 * ```
 *
 * @link http://php.net/manual/en/features.commandline.webserver.php
 */
if (PHP_SAPI === 'cli-server') {
	$_SERVER['PHP_SELF'] = '/index.php';

	if ($_SERVER['REQUEST_URI'] != '/' && file_exists('./app/webroot' . $_SERVER['REQUEST_URI'])) {
		return false;
	}
}

/**
 * Include and forward to the actual front-controller.
 */
require 'app/webroot/index.php';

?>