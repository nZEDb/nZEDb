<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////// Copy this file to settings.php and edit the options. //////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////// Scroll down to the bottom for a change log. //////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**********************************************************************************************************************/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////// MISC //////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * When we update settings.php.example, we will raise this version, you will get a message saying your settings.php
 * is out of date, you will need to update it and change the version number.
 *
 * @note Developers: When updating settings.php.example, up this version
 *                   and $current_settings_file_version in constants.php
 * @version 3
 */
define('nZEDb_SETTINGS_FILE_VERSION', 3);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////// Web Settings //////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * How many releases to show per page in list view.
 *
 * @default '50'
 */
define('ITEMS_PER_PAGE', '50');

/**
 * How many releases to show per page in cover view.
 *
 * @default '20'
 */
define('ITEMS_PER_COVER_PAGE', '20');

/**
 * How many releases maximum to display in total on browse/search/etc.
 * If you have ITEMS_PER_PAGE set to 50, and nZEDb_MAX_PAGER_RESULTS set to 125000, you would get a maximum of
 * 2,500 pages of results in searches/browse.
 *
 * @note This setting can speed up browsing releases tremendously if you have millions of releases and you keep it
 * a relatively low value.
 *
 * @default '125000'
 */
define('nZEDb_MAX_PAGER_RESULTS', '125000');

/**
 * If the PRE API page (preinfo) is open to the public or only accessible by registered / api users.
 *
 * @default false
 */
define('nZEDb_PREINFO_OPEN', false);

/**
 * Whether to check if a person is trying to send too many requests in a given amount of time,
 * lock out the person of the site for a amount of time.
 *
 * @default false
 */
define('nZEDb_FLOOD_CHECK', false);

/**
 * How many seconds should the person be locked out of the site.
 *
 * @default 5
 */
define('nZEDb_FLOOD_WAIT_TIME', 5);

/**
 * How many requests in a second can a person send to the site max before being locked out for
 * nZEDb_FLOOD_WAIT_TIME seconds.
 *
 * @default 5
 */
define('nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND', 5);

/**
 * The higher this number, the more secure the password algorithm for the website will be, at the cost
 * of server resources.
 * To find a good number for your server, run the misc/testing/Various/find_password_hash_cost.php script.
 *
 * @note It is not recommended to set this under 11.
 * @default 11
 *
 * @version 2
 */
define('nZEDb_PASSWORD_HASH_COST', 11);

/**
 * The type of search system to use on the site.
 *
 * 0 = The default system, which uses fulltext indexing (very fast but search results can be unexpected).
 * 1 = The old search system from newznab classic (much slower but produces better search results).
 * 2 = Search using sphinx real time index, see misc/sphinxsearch/README.md for installation details.
 *
 * @default 0
 */
define('nZEDb_RELEASE_SEARCH_TYPE', 0);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// Sphinx Settings ////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * This is the hostname to use when connecting to the SphinxQL server,
 *
 * @note Using localhost / 127.0.0.1 has caused me issues and only 0 worked on my local server.
 * @note See misc/sphinxsearch/README.md for installation details.
 * @default '0'
 */
define('nZEDb_SPHINXQL_HOST_NAME', '0');

/**
 * This is the port to the SphinxQL server.
 *
 * @default 9306
 */
define('nZEDb_SPHINXQL_PORT', 9306);

/**
 * This is the (optional) location to the SphinxQL server socket file, if you set the "listen" setting to a sock file.
 *
 * @default ''
 */
define('nZEDb_SPHINXQL_SOCK_FILE', '');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////// CLI Settings //////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Display text to console(terminal) output.
 *
 * @default true
 */
define('nZEDb_ECHOCLI', true);

/**
 * Rename releases using PAR2 files (if they match on PRE titles)?
 *
 * @default true
 */
define('nZEDb_RENAME_PAR2', true);

/**
 * Rename music releases using media info from the MP3/FLAC/etc tags (names are created using info found in the tags)?
 *
 * @default true
 */
define('nZEDb_RENAME_MUSIC_MEDIAINFO', true);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// Cache Settings /////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Type of cache server(s) to use:
 * 0 - disabled   ; No cache server(s) will be used.
 * 1 - memcached  ; Memcached server(s) will be used for caching.
 * 2 - redis      ; Redis server(s) will be used for caching.
 * 3 - apc/apcu   ; APC or APCu will be used for caching.
 *
 * @see https://github.com/nZEDb/nZEDb_Misc/blob/master/Guides/Various/Cache/Guide.md
 * @note Memcahed: The Memcached PHP extension must be installed.
 * @note Redis:    We use the Redis PHP extension by nicolasff. https://github.com/nicolasff/phpredis
 * @note APC:      The APC or APCu PHP extension must be installed.
 * @note APC:      Ignore these settings: nZEDb_CACHE_HOSTS / nZEDb_CACHE_SOCKET_FILE / nZEDb_CACHE_TIMEOUT
 * @default 0
 * @version 3
 */
define('nZEDb_CACHE_TYPE', 0);

/**
 * List of redis or memcached servers to connect to. Separate them by comma.
 * Host:   (string)  Address for the cache server. '127.0.0.1' for a local server.
 * Port:   (integer) Default for memcached is 11211, Default for redis is 6379
 * Weight: (integer) On redis, this is unused, set it to 0.
 *                   On memcached if you have 1 memcached server, you set this to 100.
 *                   If you have more than 1 memcached server, see the memcached documentation for more info:
 *                   http://php.net/manual/en/memcached.addserver.php
 */
define(
	'nZEDb_CACHE_HOSTS',
	serialize(
		[
			'Server1' => [
				'host'   => '127.0.0.1',
				'port'   => 11211,
				'weight' => 0
			],
		]
	)
);

/**
 * Optional path to unix socket file, leave '' if to not use.
 * If using a unix socket file, the server list is overridden.
 * This should be faster than using the host/port if your cache server is local.
 *
 * @example '/var/run/redis/redis.sock'
 * @note By default, redis and memcached do not have a socket file, you must configure them.
 * @note Read and write access is required to the socket file.
 * @default ''
 */
define('nZEDb_CACHE_SOCKET_FILE', '');

/**
 * Timeout for connecting to cache server(s).
 *
 * @default 10
 */
define('nZEDb_CACHE_TIMEOUT', 10);

/**
 * Memcached allows to compress the data, saving RAM at the expense of CPU time.
 *
 * @note Does nothing on redis.
 * @default false
 */
define('nZEDb_CACHE_COMPRESSION', false);

/**
 * Serialization is a way of converting data in PHP into strings of text which can be stored on the cache server.
 *
 * 0 - Use the PHP serializer. Recommended for most people.
 * 1 - [Requires igbinary] Use igbinary serializer which is faster and uses less memory, works
 *                         on Memcached / Redis / APC, read the notes below.
 * 2 - [Redis Only] Use no serializer.
 *
 * @note igbinary must be compiled and enabled in php.ini
 * @note APC/APCu: This setting is ignored, set this in php.ini with apc.serializer
 * @note Memcached/Redis must be compiled with igbinary support as well to use igbinary.
 * @note Read the igbinary page how to compile / enable.
 * @see https://github.com/phadej/igbinary
 * @default 0
 * @version 3
 */
define('nZEDb_CACHE_SERIALIZER', 0);

/**
 * Amount of time in seconds to expire data from the cache server.
 * The developers of nZEDb decide what should be set as short/medium/long, depending on the type of data.
 *
 * @defaults 300/600/900
 */
define('nZEDb_CACHE_EXPIRY_SHORT', 300);
define('nZEDb_CACHE_EXPIRY_MEDIUM', 600);
define('nZEDb_CACHE_EXPIRY_LONG', 900);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////// Log Settings //////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Display debug messages on console or web page.
 *
 * @default false
 */
define('nZEDb_DEBUG', false);

/**
 * Log debug messages to nzedb/resources/debug.log
 *
 * @default false
 */
define('nZEDb_LOGGING', false);

/**
 * var_dump missing autoloader files.
 *
 * @note Dev setting.
 * @default false
 */
define('nZEDb_LOGAUTOLOADER', false);

/**
 * How many log files to keep in the log folder.
 *
 * @default 20
 */
define('nZEDb_LOGGING_MAX_LOGS', 20);

/**
 * How large can the log files be in MegaBytes before we create a new one? The old files are compressed.
 *
 * @default 30
 */
define('nZEDb_LOGGING_MAX_SIZE', 30);

/**
 * The folder to put the log files in. Put quotes, example : '/var/log/nZEDb/'
 * The default is in the nZEDb root folder /resources/logs/
 *
 * @example '/var/log/nZEDb/'
 * @default nZEDb_LOGS
 */
define('nZEDb_LOGGING_LOG_FOLDER', nZEDb_LOGS);

/**
 * The name of the log file.
 * Must be alphanumeric (a-z 0-9) and contain no file extensions.
 *
 * @default 'nzedb'
 */
define('nZEDb_LOGGING_LOG_NAME', 'nzedb');

/**
 * Display memory usage in log file and debug message output?
 *
 * @default true
 */
define('nZEDb_LOGGING_LOG_MEMORY_USAGE', true);

/**
 * Display CPU load in log file and debug message output?
 *
 * @default true
 */
define('nZEDb_LOGGING_LOG_CPU_LOAD', true);

/**
 * Display running time in log file and debug message output?
 *
 * @default true
 */
define('nZEDb_LOGGING_LOG_RUNNING_TIME', true);

/**
 * Display resource usage in log file and debug message output?
 *
 * @default false
 */
define('nZEDb_LOGGING_LOG_RESOURCE_USAGE', false);

/*********************************************************************************
 * The following options require either nZEDb_DEBUG OR nZEDb_LOGGING to be true: *
 *********************************************************************************/

/**
 * Log and/or echo debug Info messages.
 *
 * @default false
 */
define('nZEDb_LOGINFO', false);

/**
 * Log and/or echo debug Notice messages.
 *
 * @default false
 */
define('nZEDb_LOGNOTICE', false);

/**
 * Log and/or echo debug Warning messages.
 *
 * @default false
 */
define('nZEDb_LOGWARNING', false);

/**
 * Log and/or echo debug Error messages.
 *
 * @default false
 */
define('nZEDb_LOGERROR', false);

/**
 * Log and/or echo debug Fatal messages.
 *
 * @default false
 */
define('nZEDb_LOGFATAL', false);

/**
 * Log and/or echo debug failed SQL queries.
 *
 * @default false
 */
define('nZEDb_LOGQUERIES', false);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////// SQL Settings //////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Strip white space (space, carriage return, new line, tab, etc) from queries before sending to MySQL.
 * This is useful if you use the MySQL slow query log.
 *
 * @note This slows down query processing, leave it false unless you turn on the MySQL slow query log.
 * @default false
 */
define('nZEDb_QUERY_STRIP_WHITESPACE', false);

/**
 * Use transactions when doing certain SQL jobs.
 * This has advantages and disadvantages.
 * If there's a problem during a transaction, MySQL can revert the row inserts which is beneficial.
 * Transactions can cause deadlocks however if you are trying to insert into the same table from another process.
 *
 * @note If all your tables are MyISAM you can set this to false, as MyISAM does not support transactions.
 * @default true
 */
define('nZEDb_USE_SQL_TRANSACTIONS', true);

/**
 * Allows the use of LOW_PRIORITY in certain DELETE queries.
 * This prevents table locks by deleting only when no SELECT queries are active on the table.
 * This works on MyISAM/ARIA, not INNODB.
 *
 * @note Does not cause any errors or warnings if enabled on INNODB.
 * @link https://dev.mysql.com/doc/refman/5.7/en/delete.html
 * @default false
 * @version 1
 */
define('nZEDb_SQL_DELETE_LOW_PRIORITY', false);

/**
 * Allows the use QUICK in certain DELETE queries.
 * This makes DELETE queries faster on MyISAM/ARIA tables by not merging index leaves.
 * Only supported on MyISAM/ARIA
 *
 * @note Does not cause any errors or warnings if enabled on INNODB.
 * @link https://dev.mysql.com/doc/refman/5.7/en/delete.html
 * @default false
 * @version 1
 */
define('nZEDb_SQL_DELETE_QUICK', false);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////// PHPMailer Settings //////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Simple constant to let us know this file is included and we should use PHPMailer library.
 * Uncomment the line below after setting the other constants.
 */
//define('PHPMAILER_ENABLED', true);

/**
 * Global "From" Address.
 * This address will be set as the From: address on every email sent by nZEDb.
 *
 * @example 'noreply@example.com'
 * @note Depending on server configurations, it may not respect this value.
 * @default '' (uses the contact email configured in 'Edit Site' settings)
 */
define('PHPMAILER_FROM_EMAIL', '');

/**
 * Global "From" Name.
 * Along with the email above, this will display as the name.
 *
 * @example 'KingCat'
 * @note Depending on server configurations, it may not respect this value.
 * @default '' (uses the site title configured in 'Edit Site' settings)
 */
define('PHPMAILER_FROM_NAME', '');

/**
 * Global "Reply-to" Address.
 * This address will be set as the Reply-to: address on every email sent by nZEDb.
 *
 * @example 'support@example.com'
 * @note It's a good idea to set this to your support email account (if possible)
 * @default '' (uses the contact email configured in 'Edit Site' settings)
 */
define('PHPMAILER_REPLYTO', '');

/**
 * Always BCC.
 * This email address will be blind carbon copied on every email sent from this site.
 *
 * @note This has very specific uses, don't enable unless you're sure you want to get the deluge.
 * @default ''
 */
define('PHPMAILER_BCC', '');

/**
 * Should we use a SMTP server to send mail?
 * If false, it will use your default settings from php.ini.
 *
 * @note If set to true, be sure to set the server settings below.
 * @default false
 */
define('PHPMAILER_USE_SMTP', false);


/*********************************************************************************
 * The following options require PHPMAILER_USE_SMTP to be true: *
 *********************************************************************************/


/**
 * This is the hostname to use if connecting to a SMTP server.
 *
 * @note You can specify main and backup hosts, delimit with a semicolon. (i.e. 'main.host.com;backup.host.com')
 * @default ''
 */
define('PHPMAILER_SMTP_HOST', '');

/**
 * TLS & SSL Support for your SMTP server.
 *
 * @note Possible values: false, 'tls', 'ssl'
 * @default 'tls'
 */
define('PHPMAILER_SMTP_SECURE','tls');

/**
 * SMTP Port
 *
 * @note Usually this is 25, 465, or 587
 * @default 587
 */
define('PHPMAILER_SMTP_PORT', 587);

/**
 * Does your SMTP host require authentication?
 *
 * @note Be sure to set credentials below if changing to true.
 * @default false
 */
define('PHPMAILER_SMTP_AUTH', false);


/*********************************************************************************
 * The following options require both PHPMAILER_USE_SMTP & PHPMAILER_SMTP_AUTH to be true: *
 *********************************************************************************/


/**
 * SMTP username for authentication.
 *
 * @default ''
 */
define('PHPMAILER_SMTP_USER','');

/**
 * SMTP password for authentication.
 *
 * @default ''
 */
define('PHPMAILER_SMTP_PASSWORD', '');

/***********************************************************************************************************************
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////// Change log ////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

2015-06-11       v3  Add support for APC or APCu extensions for caching data. Search for @version 3 for the changes.

2015-05-10       v2  Update path to find_password_hash_cost.php in comments. Search for @version 2 for the changes.

2015-05-03       v1  Track settings.php.example changes.
                     Add support for quick and low_priority on MySQL DELETE queries.
                     Search for @version 1 in this file to quickly find these additions.

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
