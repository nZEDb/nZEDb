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
// Regex to ignore categories. Leave empty ('') to not exclude any category.
// Case sensitive example: '/^(XXX|PDA|EBOOK|MP3)$/'
// Case insensitive (note the i): '/^(X264|TV)$/i'
define('SCRAPE_IRC_CATEGORY_IGNORE', '');
// Set to true to ignore a source.
define('SCRAPE_IRC_SOURCE_IGNORE',
	serialize(
		array(
			'#a.b.cd.image'               => false,
			'#a.b.console.ps3'            => false,
			'#a.b.dvd'                    => false,
			'#a.b.erotica'                => false,
			'#a.b.flac'                   => false,
			'#a.b.foreign'                => false,
			'#a.b.games.nintendods'       => false,
			'#a.b.inner-sanctum'          => false,
			'#a.b.moovee'                 => false,
			'#a.b.movies.divx'            => false,
			'#a.b.sony.psp'               => false,
			'#a.b.sounds.mp3.complete_cd' => false,
			'#a.b.teevee'                 => false,
			'#a.b.games.wii'              => false,
			'#a.b.warez'                  => false,
			'#a.b.games.xbox360'          => false,
			'#pre@corrupt'                => false,
			'#scnzb'                      => false,
			'#tvnzb'                      => false
		)
	)
);