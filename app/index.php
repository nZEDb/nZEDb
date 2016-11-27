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
 */

/**
 * Include and forward to the actual front-controller.
 */
require 'webroot/index.php';

?>