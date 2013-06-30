<?php

//=========================
// Config you must change - updated by installer.
//=========================
define('DB_TYPE', 'mysql');
define('DB_HOST', '%%DB_HOST%%');
define('DB_PORT', '%%DB_PORT%%');
define('DB_SOCKET', '%%DB_SOCKET%%');
define('DB_USER', '%%DB_USER%%');
define('DB_PASSWORD', '%%DB_PASSWORD%%');
define('DB_NAME', '%%DB_NAME%%');
define('DB_PCONNECT', false);

define('NNTP_USERNAME', '%%NNTP_USERNAME%%');
define('NNTP_PASSWORD', '%%NNTP_PASSWORD%%');
define('NNTP_SERVER', '%%NNTP_SERVER%%');
define('NNTP_PORT', '%%NNTP_PORT%%');
define('NNTP_SSLENABLED', %%NNTP_SSLENABLED%%);

define('NNTP_USERNAME_A', '%%NNTP_USERNAME_A%%');
define('NNTP_PASSWORD_A', '%%NNTP_PASSWORD_A%%');
define('NNTP_SERVER_A', '%%NNTP_SERVER_A%%');
define('NNTP_PORT_A', '%%NNTP_PORT_A%%');
define('NNTP_SSLENABLED_A', %%NNTP_SSLENABLED_A%%);

require("automated.config.php");
