<?php

$www_top = str_replace("\\","/",dirname( $_SERVER['PHP_SELF'] ));
if(strlen($www_top) == 1)
	$www_top = "";

//
// used everywhere an href is output, includes the full path to the nZEDb install
//
define('WWW_TOP', $www_top);

//
// used to refer to the /www/lib class files
//
define('WWW_DIR', realpath(dirname(__FILE__)).'/');

//
// path to smarty files
//
define('SMARTY_DIR', WWW_DIR.'lib/smarty/');

//
// number of results per page
//
define("ITEMS_PER_PAGE", "50");
