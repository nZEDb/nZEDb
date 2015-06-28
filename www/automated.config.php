<?php
// YOU SHOULD NOT EDIT ANYTHING IN THIS FILE, COPY settings.example.php TO settings.php AND EDIT THAT FILE!

define('nZEDb_MINIMUM_PHP_VERSION', '5.5.0');
define('nZEDb_MINIMUM_MYSQL_VERSION', '5.5');

define('DS', DIRECTORY_SEPARATOR);

// These are file path constants
define('nZEDb_ROOT', realpath(dirname(dirname(__FILE__))) . DS);

// Used to refer to the main lib class files.
define('nZEDb_LIB', nZEDb_ROOT . 'nzedb' . DS);
define('nZEDb_CORE', nZEDb_LIB);

define('nZEDb_CONFIGS', nZEDb_CORE . 'config' . DS);

// Used to refer to the third party library files.
define('nZEDb_LIBS', nZEDb_ROOT . 'libs' . DS);

// Used to refer to the /misc class files.
define('nZEDb_MISC', nZEDb_ROOT . 'misc' . DS);

// /misc/update/
define('nZEDb_UPDATE', nZEDb_MISC . 'update' . DS);

// /misc/update/nix/
define('nZEDb_NIX', nZEDb_UPDATE . 'nix' . DS);

// /misc/update/nix/multiprocessing/
define('nZEDb_MULTIPROCESSING', nZEDb_NIX . 'multiprocessing' . DS);

// Refers to the web root for the Smarty lib
define('nZEDb_WWW', nZEDb_ROOT . 'www' . DS);

// Used to refer to the resources folder
define('nZEDb_RES', nZEDb_ROOT . 'resources' . DS);

// Used to refer to the tmp folder
define('nZEDb_TMP', nZEDb_RES . 'tmp' . DS);

// Full path is fs to the themes folder
define('nZEDb_THEMES', nZEDb_WWW . 'themes' . DS);

// Shared theme items (pictures, scripts).
define('nZEDb_THEMES_SHARED', nZEDb_WWW . 'themes_shared' . DS);

// Path where log files are stored.
define('nZEDb_LOGS', nZEDb_RES . 'logs' . DS);

if (function_exists('ini_set') && function_exists('ini_get')) {
	$ps = (strtolower(PHP_OS) == 'windows') ? ';' : ':';
	ini_set('include_path', nZEDb_WWW . $ps . ini_get('include_path'));
}

// Path to smarty files. (not prefixed with nZEDb as the name is needed in smarty files).
define('SMARTY_DIR', nZEDb_LIBS . 'smarty' . DS);

// These are site constants
$www_top = str_replace("\\", "/", dirname($_SERVER['PHP_SELF']));
if (strlen($www_top) == 1) {
	$www_top = "";
}

// Used everywhere an href is output, includes the full path to the nZEDb install.
define('WWW_TOP', $www_top);

define('nZEDb_VERSIONS', nZEDb_LIB . 'build' . DS . 'nZEDb.xml');

$settings_file = __DIR__ . DS . 'settings.php';
if (is_file($settings_file)) {
	require_once($settings_file);
	if (php_sapi_name() == 'cli') {
		$current_settings_file_version = 3; // Update this when updating settings.example.php
		if (!defined('nZEDb_SETTINGS_FILE_VERSION') || nZEDb_SETTINGS_FILE_VERSION != $current_settings_file_version) {
			echo ("\033[0;31mNotice: Your $settings_file file is either out of date or you have not updated" .
				" nZEDb_SETTINGS_FILE_VERSION to $current_settings_file_version in that file.\033[0m" . PHP_EOL
			);
		}
		unset($current_settings_file_version);
	}
} else {
	define('ITEMS_PER_PAGE', '50');
	define('ITEMS_PER_COVER_PAGE', '20');
	define('nZEDb_ECHOCLI', true);
	define('nZEDb_DEBUG', false);
	define('nZEDb_LOGGING', false);
	define('nZEDb_LOGINFO', false);
	define('nZEDb_LOGNOTICE', false);
	define('nZEDb_LOGWARNING', false);
	define('nZEDb_LOGERROR', false);
	define('nZEDb_LOGFATAL', false);
	define('nZEDb_LOGQUERIES', false);
	define('nZEDb_LOGAUTOLOADER', false);
	define('nZEDb_QUERY_STRIP_WHITESPACE', false);
	define('nZEDb_RENAME_PAR2', true);
	define('nZEDb_RENAME_MUSIC_MEDIAINFO', true);
	define('nZEDb_CACHE_EXPIRY_SHORT', 300);
	define('nZEDb_CACHE_EXPIRY_MEDIUM', 600);
	define('nZEDb_CACHE_EXPIRY_LONG', 900);
	define('nZEDb_PREINFO_OPEN', false);
	define('nZEDb_FLOOD_CHECK', false);
	define('nZEDb_FLOOD_WAIT_TIME', 5);
	define('nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND', 5);
	define('nZEDb_USE_SQL_TRANSACTIONS', true);
	define('nZEDb_RELEASE_SEARCH_TYPE', 0);
	define('nZEDb_MAX_PAGER_RESULTS', '125000');
}
unset($settings_file);

require_once nZEDb_CORE . 'autoloader.php';
require_once nZEDb_LIBS . 'autoloader.php';

define('HAS_WHICH', nzedb\utility\Utility::hasWhich() ? true : false);

if (file_exists(__DIR__ . DS . 'config.php')) {
	require_once __DIR__ . DS . 'config.php';
}

// Check if they updated config.php for the openssl changes. Only check 1 to save speed.
if (!defined('nZEDb_SSL_VERIFY_PEER')) {
	define('nZEDb_SSL_CAFILE', '');
	define('nZEDb_SSL_CAPATH', '');
	define('nZEDb_SSL_VERIFY_PEER', '0');
	define('nZEDb_SSL_VERIFY_HOST', '0');
	define('nZEDb_SSL_ALLOW_SELF_SIGNED', '1');
}
