<?php
/***********************************************************************************************************************
 * You can use this to set the NICKNAME, REALNAME and USERNAME below.
 * You can change them manually below if you have to.
 * @note THIS MUST NOT BE EMPTY, THIS MUST ALSO BE UNIQUE OR YOU WILL NOT BE ABLE TO CONNECT TO IRC.
 **********************************************************************************************************************/
$username = '';

/***********************************************************************************************************************
 * The IRC server to connect to.
 * @note If you have issues connecting, head to https://www.synirc.net/servers and try another server.
 **********************************************************************************************************************/
define('SCRAPE_IRC_SERVER', 'irc.synirc.net');

/***********************************************************************************************************************
 * This is the port to the IRC server.
 * @note If you want use SSL/TLS, use a corresponding port (6697 or 7001 for example), and set SCRAPE_IRC_TLS to true.
 **********************************************************************************************************************/
define('SCRAPE_IRC_PORT', '6667');

/***********************************************************************************************************************
 * If you want to use SSL/TLS encryption on the IRC server, set this to true.
 * @note Make sure you use a valid SSL/TLS port in SCRAPE_IRC_PORT.
 **********************************************************************************************************************/
define('SCRAPE_IRC_TLS', false);

/***********************************************************************************************************************
 * This is the nick name visible in IRC channels.
 **********************************************************************************************************************/
define('SCRAPE_IRC_NICKNAME', "$username");

/***********************************************************************************************************************
 * This is a name that is visible to others when they type /whois nickname.
 **********************************************************************************************************************/
define('SCRAPE_IRC_REALNAME', "$username");

/***********************************************************************************************************************
 * This is used as part of your "ident" when connecting to IRC.
 * @note This is also the username for ZNC.
 **********************************************************************************************************************/
define('SCRAPE_IRC_USERNAME', "$username");

/***********************************************************************************************************************
 * This is not required by synirc, but if you use ZNC, this is required.
 * @note Put your password between quotes: 'mypassword'
 * @note If you are using ZNC and having issues, try 'username:password' or 'username/network:<password>'
 **********************************************************************************************************************/
define('SCRAPE_IRC_PASSWORD', false);

/***********************************************************************************************************************
 * This is an optional field you can use for ignoring categories.
 * @note If you do not wish to exclude any categories, leave it a empty string: ''
 * @examples Case sensitive:   '/^(XXX|PDA|EBOOK|MP3)$/'
 *           Case insensitive: '/^(X264|TV)$/i'
 **********************************************************************************************************************/
define('SCRAPE_IRC_CATEGORY_IGNORE', '');

/***********************************************************************************************************************
 * This is an optional field you can use for ignoring PRE titles.
 * @note If you do not wish to exclude any PRE titles, leave it a empty string: ''
 * @examples Case insensitive ignore German or XXX in the title: '/\.(German|XXX)\./i'
 *           This would ignore titles like:
 *           Yanks.14.06.30.Bianca.Travelman.Is.A.Nudist.XXX.MP4-FUNKY
 *           Blancanieves.Ein.Maerchen.von.Schwarz.und.Weiss.2012.German.1080p.BluRay.x264-CONTRiBUTiON
 **********************************************************************************************************************/
define('SCRAPE_IRC_TITLE_IGNORE', '');

/***********************************************************************************************************************
 * This is a list of all the sources we fetch PRE's from.
 * If you want to ignore a source, change it from false to true.
 **********************************************************************************************************************/
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
			'#tvnzb'                      => false,
			'omgwtfnzbs'                  => false,
			'orlydb'                      => false,
			'prelist'                     => false,
			'srrdb'                       => false,
			'u4all.eu'                    => false,
			'zenet'                       => false
		)
	)
);
