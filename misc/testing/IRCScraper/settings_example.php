<?php
// If lazy set all usernames/nicknames/names here.
// MAKE SURE THIS IS UNIQUE, IF SOMEONE HAS THE USERNAME ALREADY YOU WILL GET A BUNCH OF ERRORS, YOU HAVE BEEN WARNED.
$username = '';

// EFNET server details.
define('SCRAPE_IRC_EFNET_SERVER', 'irc.Prison.NET'); // Efnet server address, change if you have issues connecting.
define('SCRAPE_IRC_EFNET_PORT', '6667');             // Port for efnet server.
define('SCRAPE_IRC_EFNET_NICKNAME', "$username");    // Nick name (this is the nickname everyone sees in the channel)
define('SCRAPE_IRC_EFNET_REALNAME', "$username");    // This is a name that people see in /whois, you can set this to your nickname.
define('SCRAPE_IRC_EFNET_USERNAME', "$username");    // This is part of your hostname, you can set this the same as nickname. This is also used to log in to ZNC.
define('SCRAPE_IRC_EFNET_PASSWORD', false);          // This is used for bouncers like ZNC, set this false or '' if you don't have a bouncer.
define('SCRAPE_IRC_EFNET_ENCRYPTION', false);        // Set to true to use TLS encryption (make sure you change the port to a SSL one).
// List of ignored channels, separated by commas. ie '#alt.binaries.teevee,#alt.binaries.moovee' for a single channel : '#alt.binaries.teevee'
define('SCRAPE_IRC_EFNET_IGNORED_CHANNELS', '');

define('SCRAPE_IRC_C_Z_BOOL', false); // True uses Corrupt, False uses Zenet. (they both PRE the same stuff). If you have trouble with one, use the other.

// Corrupt-Net server details.
define('SCRAPE_IRC_CORRUPT_SERVER', 'irc.corrupt-net.org'); // This should not be changed, since this is the only address to corrupt.
define('SCRAPE_IRC_CORRUPT_PORT', '6667');
define('SCRAPE_IRC_CORRUPT_NICKNAME', "$username");
define('SCRAPE_IRC_CORRUPT_REALNAME', "$username");
define('SCRAPE_IRC_CORRUPT_USERNAME', "$username");
define('SCRAPE_IRC_CORRUPT_PASSWORD', false);
define('SCRAPE_IRC_CORRUPT_ENCRYPTION', false);

// Zenet server details.
define('SCRAPE_IRC_ZENET_SERVER', 'irc.zenet.org');
define('SCRAPE_IRC_ZENET_PORT', '6667');
define('SCRAPE_IRC_ZENET_NICKNAME', "$username");
define('SCRAPE_IRC_ZENET_REALNAME', "$username");
define('SCRAPE_IRC_ZENET_USERNAME', "$username");
define('SCRAPE_IRC_ZENET_PASSWORD', false);
define('SCRAPE_IRC_ZENET_ENCRYPTION', false);