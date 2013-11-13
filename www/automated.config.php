<?php
define('DS', DIRECTORY_SEPARATOR);

define('nZEDb_ROOT',	realpath(dirname(dirname(__FILE__))) . DS);
define('nZEDb_WWW',		nZEDb_ROOT	. 'www' . DS);
define('nZEDb_LIB',		nZEDb_WWW	. 'lib' . DS);
define('nZEDb_MISC',	nZEDb_WWW	. 'misc' . DS);

define('nZEDb_DEBUG', false);

$www_top = str_replace("\\","/",dirname($_SERVER['PHP_SELF']));
if(strlen($www_top) == 1)
	$www_top = "";

// Used everywhere an href is output, includes the full path to the nZEDb install.
define('WWW_TOP', $www_top);

// Used to refer to the /www/lib class files.
define('WWW_DIR', nZEDb_WWW);

// Used to refer to the /misc class files.
define('MISC_DIR', nZEDb_MISC);

// Path to smarty files.
define('SMARTY_DIR', nZEDb_LIB . 'smarty' . DS);

// Path to themes directory.
define('THEMES_DIR', WWW_TOP.'themes');

// Number of results per page.
define("ITEMS_PER_PAGE", "100");
define("ITEMS_PER_COVER_PAGE", "50");
