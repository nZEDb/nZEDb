<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * ### Configuring backend database connections
 *
 * Lithium supports a wide variety relational and non-relational databases, and is designed to allow
 * and encourage you to take advantage of multiple database technologies, choosing the most optimal
 * one for each task.
 *
 * As with other `Adaptable`-based configurations, each database configuration is defined by a name,
 * and an array of information detailing what database adapter to use, and how to connect to the
 * database server. Unlike when configuring other classes, `Connections` uses two keys to determine
 * which class to select. First is the `'type'` key, which specifies the type of backend to
 * connect to. For relational databases, the type is set to `'database'`. For HTTP-based backends,
 * like CouchDB, the type is `'http'`. Some backends have no type grouping, like MongoDB, which is
 * unique and connects via a custom PECL extension. In this case, the type is set to `'MongoDb'`,
 * and no `'adapter'` key is specified. In other cases, the `'adapter'` key identifies the unique
 * adapter of the given type, i.e. `'MySql'` for the `'database'` type, or `'CouchDb'` for the
 * `'http'` type. Note that while adapters are always specified in CamelCase form, types are
 * specified either in CamelCase form, or in underscored form, depending on whether an `'adapter'`
 * key is specified. See the examples below for more details.
 *
 * ### Multiple environments
 *
 * As with other `Adaptable` classes, `Connections` supports optionally specifying different
 * configurations per named connection, depending on the current environment. For information on
 * specifying environment-based configurations, see the `Environment` class.
 *
 * @see lithium\core\Adaptable
 * @see lithium\core\Environment
 */
use lithium\data\Connections;

/**
 * Uncomment this configuration to use MongoDB as your default database.
 */
// Connections::add('default', array(
// 	'type' => 'MongoDb',
// 	'host' => 'localhost',
// 	'database' => 'my_app'
// ));

/**
 * Uncomment this configuration to use CouchDB as your default database.
 */
// Connections::add('default', array(
// 	'type' => 'http',
// 	'adapter' => 'CouchDb',
// 	'host' => 'localhost',
// 	'database' => 'my_app'
// ));

/**
 * Uncomment this configuration to use MySQL as your default database.
 *
 * Strict mode can be enabled or disabled, older MySQL versions were
 * by default non-strict.
 */
// Connections::add('default', array(
// 	'type' => 'database',
// 	'adapter' => 'MySql',
// 	'host' => 'localhost',
// 	'login' => 'root',
// 	'password' => '',
// 	'database' => 'my_app',
// 	'encoding' => 'UTF-8',
// 	'strict' => false
// ));

\lithium\util\Inflector::rules('uninflected', ['predb']);

$installed = nZEDb_CONFIGS . 'install.lock';

// Check for install.lock first. If it exists, so should config.php
if (file_exists($installed)) {
	// This allows us to set up a db config separate to that created by /install
	$config1 = LITHIUM_APP_PATH . DS . 'config' . DS . 'db-config.php';
	$config2 = nZEDb_CONFIGS . 'config.php';
	$config = file_exists($config1) ? $config1 : $config2;

	if (!file_exists($config)) {
		throw new \ErrorException(
			"No valid configuration file found at '$config'"
		);
	}
	require_once $config;

	switch (DB_SYSTEM) {
		case 'mysql':
			$adapter = 'MySql';
			break;
		case 'pgsql':
			$adapter = 'PostgreSql';
			break;
		default:
			break;
	}

	if (isset($adapter)) {
		if (empty(DB_SOCKET)) {
			$host = empty(DB_PORT) ? DB_HOST : DB_HOST . ':' . DB_PORT;
		} else {
			$host = DB_SOCKET;
		}

		Connections::add('default',
			[
				'type'       => 'database',
				'adapter'    => $adapter,
				'host'       => $host,
				'login'      => DB_USER,
				'password'   => DB_PASSWORD,
				'database'   => DB_NAME,
				'encoding'   => 'UTF-8',
				'persistent' => false,
				'timezone'   => ini_get('date.timezone'),
			]
		);

		\nzedb\utility\Misc::setCoversConstant(
			\app\models\Settings::value('site.main.coverspath')
		);
	} else {
		throw new \ErrorException(
			"No valid database adapter provided in configuration file '$config'"
		);
	}
} else if (file_exists(nZEDb_CONFIGS . 'dev-config.json')) {
	$config = json_decode(file_get_contents(nZEDb_CONFIGS . 'dev-config.json'), true);
	$db =& $config['db'];

	switch ($db['system']) {
		case 'mysql':
			$adapter = 'MySql';
			break;
		case 'pgsql':
			$adapter = 'PostgreSql';
			break;
		default:
			throw new \RuntimeException("Invalid database system in dev-config file!");
			break;
	}

	if (empty($db['socket'])) {
		$host = empty($db['port']) ? $db['host'] : $db['host'] . ':' . $db['port'];
	} else {
		$host = $db['socket'];
	}

	Connections::add('default',
		[
			'type'       => 'database',
			'adapter'    => $adapter,
			'host'       => $host,
			'login'      => $db['user'],
			'password'   => $db['password'],
			'database'   => $db['database'],
			'encoding'   => 'UTF-8',
			'persistent' => $db['persist'],
			'timezone'   => ini_get('date.timezone'),
			// If enabled this forces all table column names to be lower-cased. This should only
			// be needed by users with long standing databases that were created with upper-cased
			// names for some fields.
			'lowercase'  =>	false,
		]
	);

	if (nZEDb_DEBUG) {
		if (!defined()) {
			define('DB_SYSTEM', strtolower($adapter));
		}

		if (!defined()) {
			define('DB_HOST', $db['host']);
		}

		if (!defined()) {
			define('DB_PORT', $db['port']);
		}

		if (!defined()) {
			define('DB_SOCKET', $db['socket']);
		}

		if (!defined()) {
			define('DB_USER', $db['user']);
		}

		if (!defined()) {
			define('DB_PASSWORD', $db['password']);
		}

		if (!defined()) {
			define('DB_NAME', $db['database']);
		}

		if (!defined()) {
			define('DB_PCONNECT', $db['persist']);
		}

		$usp =& $config['usp'];

		define('NNTP_USERNAME', $usp['connection1']['username']);
		define('NNTP_PASSWORD', $usp['connection1']['password']);
		define('NNTP_SERVER', $usp['connection1']['server']);
		define('NNTP_PORT', $usp['connection1']['port']);
		define('NNTP_SSLENABLED', $usp['connection1']['ssl']);
		define('NNTP_SOCKET_TIMEOUT', $usp['connection1']['timeout']);

		define('NNTP_USERNAME_A', $usp['connection2']['username']);
		define('NNTP_PASSWORD_A', $usp['connection2']['password']);
		define('NNTP_SERVER_A', $usp['connection2']['server']);
		define('NNTP_PORT_A', $usp['connection2']['port']);
		define('NNTP_SSLENABLED_A', $usp['connection2']['ssl']);
		define('NNTP_SOCKET_TIMEOUT_A', $usp['connection2']['timeout']);
	}

	\nzedb\utility\Misc::setCoversConstant(
		\app\models\Settings::value('site.main.coverspath')
	);
} else {
	Connections::add('mock',
		[
			'type'     => 'database',
			'adapter'  => 'Mock',
			'host'     => 'localhost',
			'port'     => '3306',
			'login'    => 'root',
			'password' => 'root_pass',
			'database' => 'nZEDb',
			'encoding' => 'UTF-8',
			'timezone' => ini_get('date.timezone'),
		]
	);
}

?>
