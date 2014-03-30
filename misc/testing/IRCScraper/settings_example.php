<?php
// If you are lazy, just change this, or else go and change everything else you need to.
$username = '';

// EFNET server details.
define('SCRAPE_IRC_EFNET_SERVER', 'irc.Prison.NET'); // Efnet server address, change if you have issues connecting.
define('SCRAPE_IRC_EFNET_PORT', '6667');             // Port for efnet server.
define('SCRAPE_IRC_EFNET_NICKNAME', "$username");    // Nick name (this is the nickname everyone sees in the channel)
define('SCRAPE_IRC_EFNET_REALNAME', "$username");    // This is a name that people see in /whois, you can set this to your nickname.
define('SCRAPE_IRC_EFNET_USERNAME', "$username");    // This is part of your hostname, you can set this the same as nickname. This is also used to log in to ZNC.
define('SCRAPE_IRC_EFNET_PASSWORD', false);          // This is used for bouncers like ZNC, set this false or '' if you don't have a bouncer.

// Corrupt-Net server details.
define('SCRAPE_IRC_CORRUPT_SERVER', 'irc.corrupt-net.org'); // This should not be changed, since this is the only address to corrupt.
define('SCRAPE_IRC_CORRUPT_PORT', '6667');
define('SCRAPE_IRC_CORRUPT_NICKNAME', "$username");
define('SCRAPE_IRC_CORRUPT_REALNAME', "$username");
define('SCRAPE_IRC_CORRUPT_USERNAME', "$username");
define('SCRAPE_IRC_CORRUPT_PASSWORD', false);

// Zenet server details.
define('SCRAPE_IRC_ZENET_SERVER', 'irc.corrupt-net.org');
define('SCRAPE_IRC_ZENET_PORT', '6667');
define('SCRAPE_IRC_ZENET_NICKNAME', "$username");
define('SCRAPE_IRC_ZENET_REALNAME', "$username");
define('SCRAPE_IRC_ZENET_USERNAME', "$username");
define('SCRAPE_IRC_ZENET_PASSWORD', false);