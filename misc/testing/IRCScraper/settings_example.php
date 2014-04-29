<?php
// If lazy set all usernames/nicknames/names here.
// MAKE SURE THIS IS UNIQUE, IF SOMEONE HAS THE USERNAME ALREADY YOU WILL GET A BUNCH OF ERRORS, YOU HAVE BEEN WARNED.
$username = '';

// https://www.synirc.net/servers Try another server if you have issues.
define('SCRAPE_IRC_SERVER', 'contego.ny.us.synirc.net');
// Use Port 6697 or 7001 and set SCRAPE_IRC_TLS to true for encryption.
define('SCRAPE_IRC_PORT', '6667');
define('SCRAPE_IRC_TLS', false);
define('SCRAPE_IRC_NICKNAME', "$username");
define('SCRAPE_IRC_REALNAME', "$username");
define('SCRAPE_IRC_USERNAME', "$username");
// Set to false if you need no password. Use a string (quoted text) if you need a password.
define('SCRAPE_IRC_PASSWORD', false);
