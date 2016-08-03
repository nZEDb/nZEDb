<?php
// YOU SHOULD NOT EDIT ANYTHING IN THIS FILE,
// COPY .../nzedb/config/settings.example.php TO .../nzedb/config/settings.php AND EDIT THAT FILE!

define('nZEDb_MINIMUM_PHP_VERSION', '5.6.9');
define('nZEDb_MINIMUM_MYSQL_VERSION', '5.5');

define('DS', DIRECTORY_SEPARATOR);

// These are file path constants
define('nZEDb_ROOT', realpath(dirname(__DIR__)) . DS);

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

// Used to refer to the resources folder
define('nZEDb_RES', nZEDb_ROOT . 'resources' . DS);

// Path where log files are stored.
define('nZEDb_LOGS', nZEDb_RES . 'logs' . DS);

// Smarty's cache.
define('nZEDb_SMARTY_CACHE', nZEDb_RES . 'smarty' . DS . 'cache/');

// Smarty's configuration files.
define('nZEDb_SMARTY_CONFIGS', nZEDb_RES .'smarty' . DS . 'configs/');

// Smarty's compiled template cache.
define('nZEDb_SMARTY_TEMPLATES', nZEDb_RES . 'smarty' . DS . 'templates_c/');

// Used to refer to the tmp folder
define('nZEDb_TMP', nZEDb_RES . 'tmp' . DS);

// Refers to the web root for the Smarty lib
define('nZEDb_WWW', nZEDb_ROOT . 'www' . DS);

// Full path is fs to the themes folder
define('nZEDb_THEMES', nZEDb_WWW . 'themes' . DS);

// Shared theme items (pictures, scripts).
define('nZEDb_THEMES_SHARED', nZEDb_THEMES . 'shared' . DS);

define('nZEDb_VERSIONS', nZEDb_ROOT . 'build' . DS . 'nzedb.xml');
