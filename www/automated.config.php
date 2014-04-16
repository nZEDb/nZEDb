<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////// Start of user changeable settings. //////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * How many releases to show per page in list view.
 * @default 50
 */
define("ITEMS_PER_PAGE", "50");
/**
 * How many releases to show per page in cover view.
 * @default 20
 */
define("ITEMS_PER_COVER_PAGE", "20");

/**
 * Display text to console(terminal) output.
 * @default true
 */
define('nZEDb_ECHOCLI', true);
/**
 * Display debug messages on console or web page.
 * @default false
 */
define('nZEDb_DEBUG', false);

/**
 * Log debug messages to nzedb/resources/debug.log
 * @default false
 */
define('nZEDb_LOGGING', false);

/*********************************************************************************
 * The following options require either nZEDb_DEBUG OR nZEDb_LOGGING to be true: *
 *********************************************************************************/
/**
 * Log and/or echo debug Info messages.
 * @default false
 */
define('nZEDb_LOGINFO', false);
/**
 * Log and/or echo debug Notice messages.
 * @default false
 */
define('nZEDb_LOGNOTICE', false);
/**
 * Log and/or echo debug Warning messages.
 * @default false
 */
define('nZEDb_LOGWARNING', false);
/**
 * Log and/or echo debug Error messages.
 * @default false
 */
define('nZEDb_LOGERROR', false);
/**
 * Log and/or echo debug Fatal messages.
 * @default false
 */
define('nZEDb_LOGFATAL', false);
/**
 * Log and/or echo debug failed SQL queries.
 * @default false
 */
define('nZEDb_LOGQUERIES', false);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// End of user changeable settings./ //////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

require_once nZEDb_CORE . 'autoloader.php';
require_once nZEDb_LIBS . 'autoloader.php';
require_once SMARTY_DIR . 'autoloader.php';

define('HAS_WHICH', nzedb\utility\Utility::hasWhich() ? true : false);
