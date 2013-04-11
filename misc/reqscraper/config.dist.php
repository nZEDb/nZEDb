<?php

define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_USER', 'allfilled');
define('DB_PASSWORD', 'allfilled');
define('DB_NAME', 'allfilled');

define('MAGPIE_CACHE_ON', 0);
 
mysql_pconnect(DB_HOST, DB_USER, DB_PASSWORD)
        or die("fatal error: could not connect to database!");
 
mysql_select_db(DB_NAME)
        or die("fatal error: could not select database!");
        
?>