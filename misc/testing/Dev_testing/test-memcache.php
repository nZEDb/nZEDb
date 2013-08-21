<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
if (!extension_loaded('memcache'))
{
	print_r(get_loaded_extensions());
	exit("You must install the memcache php extension. (sudo apt-get install memcached php5-memcache)\n");
}
$memcache = new Memcached();
if ($memcache !== false)
	exit(print_r($memcache->Server_Stats())."\nIf you have a long list of items above this then your memcached server is probably working fine.\n");
else
	exit("Make sure your host/port are set right in config.php for memcache.\n");
