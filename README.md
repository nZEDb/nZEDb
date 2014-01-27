nZEDb automatically scans the usenet similar to the way google search bots scan the internet. It does this by collecting usenet headers and temporarily storing them in a SQL database. It provides a web-based front-end providing search, browse and programmable (API) functionality.

This project is a fork of the open source usenet indexer newznab plus: https://github.com/anth0/nnplus

nZEDb improves upon the original design, implementing several new features including:
	
- Optional multi-threaded processing (header retrieval, release creation, post-processing etc)
- NNTP-Proxy providing connection pooling (greatly reduces NNTP session establishment & tear-down between nZEDb and the usenet service provider)
- Advanced search features (name, subject, category, post-date etc)
- Intelligent local caching of metadata
- Optional tmux (terminal session multiplexing) engine that provides thread, database and performance monitoring
- Image and video samples
- sabnzbd integration web, API and pause/resume
- CouchPotato integration web and API

  
## Prerequisites

System Administration know-how. nZEDb is not plug-n-play software. Installation and operation requires a moderate amount of administration experience. nZEDb is designed and developed with GNU/Linux operating systems. Certain features are not available are on other platforms. A competent Windows administrator should be able to run nZEDb on a Windows OS.
    
### Hardware
	
    4GB RAM, 2 cores(threads) and 20GB disk space minimum.
It does run on a Raspberry Pi for experienced users with very modest expectations.   
    
### Software

	PHP 5.4+ (and various modules)
    MySQL 5.5+ (Postgres support is Work-In-Progress)
    Python 2.7 or 3.0 (and various modules)
The installation guides have more detailed software requirements.

## Installation

Specific installation guides for common Operating Systems can be found on the nZEDb github wiki: https://github.com/nZEDb/nZEDb/wiki/Install-Guides

## Getting Started

In general, it's best to start with the simple screen scripts running in single-threaded mode. Enable one or two groups and slowly add more as you become familar with the application. Later, as required, enable mutli-threading and perhaps try the various tmux modii.

### Support

There is a web forum were you may search for issues previously encountered by others: 
http://nzedb.com/

Also on IRC: irc.synirc.net #nZEDb

### Note

The nZEDb team are not responsible for what is posted on the usenet. Best efforts are made to avoid hazardous content (e.g. virii) by nZEDb's automated processess. If you find any objectionable content, please direct any complaints to your usenet provider.

### The Team

Kevin123, jonnyboy, Miatrix, zombu2, Codeslave, sinfuljosh, ugo, Whitelighter and archer}<br /><br />
Paypal: <a href="http://nzedb.com/index.php?action=treasury"><img src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" alt="PayPal - The safer, easier way to pay online!" /></a>

Bitcoin wallet: 1LrrFbXn4QfGUokLppHVPQHAzmnAPbgV2M

<hr>



	
	Once you have set all the options, you can enable groups, start with a few groups then
	over the course of a few days you can enable more. I don't recommend enabling all the groups unless you have
	good hardware and mysql knowledge.
	
	At this point you are ready to use the scripts, try the scripts in misc/update_scripts,
	update_binaries.php downloads usenet articles into the mysql database,
	update_releases.php attempts to group these articles into releases and create NZB files.
	
	If you want an automated way of doing this, see the nix_scripts folder. win_scripts is non functional right now.






 be created.
	* search and rawsearch requests lose page css styling - use the provided apache vhost settings.
	* Server did not return article numbers 1234567 - this isnt necessarily a bad thing, see section on missing parts
	* connection timed out. Reconnecting... Cannot connect to server *******: Already connected, disconnect first! - 
	disable compressed headers
	* session error during install step1 - set register_globals to off.
	* Warning: Wrong parameter count for strstr() in newznab\www\lib\TMDb.php on line 354 - wrong php version, requires 5.3+
	* Strict Standards: Non-static method PEAR::isError() should not be called statically - set php.ini error_reporting = E_ALL ^ E_STRICT
	* Error 502 Bad Gateway - error at $cfg->pearCheck = @include('System.php'); solved by adding in open_basedir path to pear
	* Call to undefined function curl_init() - Make sure you are using the right php.ini file. If you are using WAMP, then 
	the php.ini file that apache uses is in the apache /bin folder (not the php.ini in wamp/php). The php cli will use 
	the first php.ini it can find in the windows path environment variable. In my case, this was an old version in 
	another php directory I set up. Once I deleted that, it used the version in the /wamp/php directory.
	* no previews or media info - check unrar version > 3.8
	
DEBUGGING
	switch php.ini error_reporting to E_ALL and ensure logging to browser is enabled.
	
DEVELOPMENT
	\db\schema.sql is latest database schema. should be able to rerun in and create new blank schema
	\misc\ for general docs and useful info, nothing in here is referenced by the application
	\misc\update_scripts\ shell and batch scripts and php files to call the updating of index from cli
	\nzbfiles default folder for all gzipped nzbs to be stored
	\www\install installer files
	\www\lib\framework few general classes for db/http code
	\www\lib\smarty copy of a fairly recent smarty lib
	\www\lib\ all classes used in the app, typically named same as its database entity
	\www\covers\ all covers downloaded for releases
	\www\views\templates\admin all templates used by the admin pages
	\www\views\templates\frontend all templates used by the user pages
	\www\pages\ controllers for every frontend page in the system
	\www\admin\ all php pages used by the admin
	\www\theme\<yourtheme> blank area for implementation specific ui customisations
	\www\views\scripts\ js dumping ground

HALL OF FAME
(just some of the) people who've helped along the way.
	iota@irc.cyberarmy.net		regexs,sessions
	enstyne@irc.cyberarmy.net	regexs
	fatfecker@newznab			mediainfo ffmpeg tv
	gizmore@wechall.net			password,hash
	lhbandit@nzbs.org			yenc,nntp,bokko,dev	
	dryes@nzbs.org				anidb	
	bb@newznab					dev
	jayhawk@nzb.su				testing,icons	
	midgetspy@sickbeard			rage integration,api
	ueland@newznab				installer
	ensi@ensisoft.com			api
	hecks@tvnzb					rar api
	michael@newznab				dev
	sakarias@newznab			testing
	pairdime@sabnzbd			jquery,css
	pmow@sabnzbd				headers,backfill
	bigdave@newznab				testing
	duz@sabnzbd					yenc
	inpheaux@sabnzbd			design,nzb
	spooge@newznab				testing
	sy@newznab					testing, regexs, amazon
	magegminds@newznab			lighttpd rewrite rules
	trizz@newznab				lighttpd rewrite rules
	fubaarr@newznab				testing
	mobiKalw@newznab			testing
	crudehung@newznab			nginx rewrite rules
	www.famfamfam.com			icons
