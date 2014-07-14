<?php
// Test the memcache connection.
require_once dirname(__FILE__) . '/../../../www/config.php';
use nzedb\db\Settings;
use nzedb\db\Mcached;

$pdo = new Settings();
$c = new ColorCLI();

if (!extension_loaded('memcache'))
{
	print_r(get_loaded_extensions());
	exit($c->error("\nYou must install the memcache php extension. (sudo apt-get install memcached php5-memcache)\n"));
}

$memcache = new Mcached();

if ($memcache !== false) {
	exit(print_r($memcache->Server_Stats())
	. $c->header("\nIf you have a long list of items above this then your memcached server is probably working fine."));
} else {
	exit($c->error("\nMake sure your host/port are set right in config.php for memcache.\n"));
}
