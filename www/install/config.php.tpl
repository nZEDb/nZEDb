<?php

//=========================
// Config you must change - updated by installer.
//=========================
define('DB_SYSTEM', '%%DB_SYSTEM%%');
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
// If you want to use TLS or SSL on the NNTP connection (the NNTP_PORT must be able to support encryption).
define('NNTP_SSLENABLED', %%NNTP_SSLENABLED%%);
// If we lose connection to the NNTP server, this is the time in seconds to wait before giving up.
define('NNTP_SOCKET_TIMEOUT', '%%NNTP_SOCKET_TIMEOUT%%');

define('NNTP_USERNAME_A', '%%NNTP_USERNAME_A%%');
define('NNTP_PASSWORD_A', '%%NNTP_PASSWORD_A%%');
define('NNTP_SERVER_A', '%%NNTP_SERVER_A%%');
define('NNTP_PORT_A', '%%NNTP_PORT_A%%');
define('NNTP_SSLENABLED_A', %%NNTP_SSLENABLED_A%%);
define('NNTP_SOCKET_TIMEOUT_A', '%%NNTP_SOCKET_TIMEOUT_A%%');

// Location to CA bundle file on your system. You can download one here: http://curl.haxx.se/docs/caextract.html
define('nZEDb_SSL_CAFILE', '%%nZEDb_SSL_CAFILE%%');
// Path where openssl cert files are stored on your system, this is a fall back if the CAFILE is not found.
define('nZEDb_SSL_CAPATH', '%%nZEDb_SSL_CAPATH%%');
// Use the aforementioned CA bundle file to verify remote SSL certificates when connecting to a server using TLS/SSL.
define('nZEDb_SSL_VERIFY_PEER', '%%nZEDb_SSL_VERIFY_PEER%%');
// Verify the host is who they say they are.
define('nZEDb_SSL_VERIFY_HOST', '%%nZEDb_SSL_VERIFY_HOST%%');
// Allow self signed certificates. Note this does not work on CURL as CURL does not have this option.
define('nZEDb_SSL_ALLOW_SELF_SIGNED', '%%nZEDb_SSL_ALLOW_SELF_SIGNED%%');

require_once 'automated.config.php';