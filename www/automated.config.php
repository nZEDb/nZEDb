<?php
$www_top = str_replace("\\","/",dirname($_SERVER['PHP_SELF']));
if(strlen($www_top) == 1)
	$www_top = "";

// Used everywhere an href is output, includes the full path to the nZEDb install.
define('WWW_TOP', $www_top);

// Used to refer to the /www/lib class files.
define('WWW_DIR', realpath(dirname(__FILE__)).'/');

// Used to refer to the /misc class files.
define('MISC_DIR', realpath(dirname(__FILE__)).'/../misc/');

// Path to smarty files.
define('SMARTY_DIR', WWW_DIR.'lib/smarty/');

// Path to themes directory.
define('THEMES_DIR', WWW_TOP.'themes');

// Number of results per page.
define("ITEMS_PER_PAGE", "50");
define("ITEMS_PER_COVER_PAGE", "20");
