<?php
define('nZEDb_DEBUG', false);

define('DS', DIRECTORY_SEPARATOR);

// These are file path constants
define('nZEDb_ROOT', realpath(dirname(dirname(__FILE__))) . DS);

// Used to refer to the /misc class files.
define('nZEDb_MISC', nZEDb_ROOT . 'misc' . DS);

define('nZEDb_WWW', nZEDb_ROOT . 'www' . DS);

// Used to refer to the /www/lib class files.
define('nZEDb_LIB', nZEDb_WWW . 'lib' . DS);

if (function_exists('ini_set') && function_exists('ini_get')) {
	$ps = (strtolower(PHP_OS) == 'windows') ? ';' : ':';
	ini_set('include_path', nZEDb_WWW . $ps . ini_get('include_path'));
}

// Path to smarty files. (not prefixed with nZEDb as the name is needed in smarty files).
define('SMARTY_DIR', nZEDb_LIB . 'smarty' . DS);

// These are site constants
$www_top = str_replace("\\","/",dirname($_SERVER['PHP_SELF']));
if(strlen($www_top) == 1)
	$www_top = "";

// Used everywhere an href is output, includes the full path to the nZEDb install.
define('WWW_TOP', $www_top);

// Path to themes directory.
define('THEMES_DIR', WWW_TOP.'themes');

// Number of results per page.
define("ITEMS_PER_PAGE", "100");
define("ITEMS_PER_COVER_PAGE", "50");
