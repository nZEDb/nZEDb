<?php
// YOU SHOULD NOT EDIT ANYTHING IN THIS FILE, COPY settings.php.example TO settings.php AND EDIT THAT FILE!

define('DS', DIRECTORY_SEPARATOR);

// These are file path constants
define('nZEDb_ROOT', realpath(dirname(dirname(__FILE__))) . DS);

// Used to refer to the main lib class files.
define('nZEDb_LIB', nZEDb_ROOT . 'nzedb' . DS);
define('nZEDb_CORE', nZEDb_LIB);

// Used to refer to the third party library files.
define('nZEDb_LIBS', nZEDb_ROOT . 'libs' . DS);

// Used to refer to the /misc class files.
define('nZEDb_MISC', nZEDb_ROOT . 'misc' . DS);

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

if (is_file(__DIR__ . DS . 'settings.php')) {
	require_once(__DIR__ . DS . 'settings.php');
	// Remove this in the future, this is for those who are nothing updating their settings.php file.
	if (!defined('nZEDb_LOGAUTOLOADER')) {
		define('nZEDb_LOGAUTOLOADER', false);
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
}

require_once nZEDb_CORE . 'autoloader.php';
require_once nZEDb_LIBS . 'autoloader.php';
require_once SMARTY_DIR . 'autoloader.php';

define('HAS_WHICH', nzedb\utility\Utility::hasWhich() ? true : false);

if (file_exists(__DIR__ . DS . 'config.php')) {
	require_once __DIR__ . DS . 'config.php';
}
