<?php
// This generates a username for you if you don't want to make one up.
$randomUser = get_current_user() . substr(md5(uniqid(true)), 2, 8);

// IRC EFNET server address. Change this only if you have issues.
define('SCRAPE_IRC_SERVER', 'irc.Prison.NET');
// Port for the server.
define('SCRAPE_IRC_PORT', '6667');
// Nick name in the IRC channel.
define('SCRAPE_IRC_NICKNAME', "$randomUser");
// User name, used to log in to the server (like a ZNC server). Use the same as Nick name if you don't know what this is for.
define('SCRAPE_IRC_USERNAME', "$randomUser");
// Password used to log in to the server (like a ZNC server). Leave false if you don't require a password.
define('SCRAPE_IRC_PASSWORD', false);
// "Real name" for IRC server. Use the same as Nick name if you don't know what this is for.
define('SCRAPE_IRC_REALNAME', "$randomUser");