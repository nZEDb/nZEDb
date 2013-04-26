MYSQL Patches for nZEDb can be found here for now : https://github.com/nZEDb/Patches-for-nZEDb

------------------------------------------------------------------------------------------------
nZEDb is a fork of the open source usenet indexer newznab plus : https://github.com/anth0/nnplus

Some of the differences between that version of newznab and our indexer are:

	The ability to create releases without the user having to create a regex. (That old version of newznab did not have updated regex.)
	Using the NZB file for post processing and fetching NFO files.
	Finding NFO files without a .nfo extension.
	Having both a subject name and a "clean" name and the ability to search either.
	Advanced search, which is able to search by name, subject, poster, date, etc..
	Importing NZB files directly to the NZB folder and the releases table.
	Importing NZB files using the mysql load into file command.
	Threading update_binaries, backfill and post processing using python. (soon update_releases)
	Custom tmux and screen scripts.
	Changing to php mysqli.
	Using autocommit/rollback features of mysqli for innodb.
	Postprocessing books, a book page (with book covers and book searching).
	Fixing most of the post processing issues.
	Using trakt.tv API to find missing IMDB and TVRage ID's.
	Adding the amazon associate tag for fetching covers and xml information.
	Script to fix release names using NFO's, file names and release names.
	Better categorization of releases.
	Most of the scripts in misc/update_scripts have been overhauled to have more options / better output.
	Changes to the website. (too many to list)
	XFeature GZIP compression, by wafflehouse : http://pastebin.com/A3YypDAJ
	Etc.. (see the commits to see a full list of changes). More to come.

Installation:

	Please view one of the two installation files in this folder.
	If you are on windows, you can attempt to use a newznab guide.

Post-Installation:

	(After you have installed nZEDb, went through the install steps and are on the admin page...)
	
	Most of the default settings are fine, I will mention the ones worth changing.
	
	The 2 amazon keys and the associate tag are needed to fetch anything from amazon. The trakt.tv key is optional,
	but it can help fetching extra information when tvrage and the NFO fails.
	
	Setting the paths to unrar/ffmpeg/mediainfo is optional, but unrar is recommended for getting names out of releases
	and finding passwords in releases.
	
	If you have set the path to unrar, deep rar inspection is recommended.
	
	Compressed headers is recommended if your provider supports XFeature gzip compression.
	
	----
	
	Once you have set all the options, you can enable groups, start with a few groups then
	over the course of a few days you can enable more. I don't recommend enabling all the groups unless you have
	good hardware and mysql knowledge.
	
	At this point you are ready to use the scripts, try the scripts in misc/update_scripts,
	update_binaries.php downloads usenet articles into the mysql database,
	update_releases.php attempts to group these articles into releases and create NZB files.
	
	If you want an automated way of doing this, see the nix_scripts folder. win_scripts is non functional right now.

	To clean up the release names, check out fixReleaseNames.php in misc/testing.

Original Newznab readme:

ABOUT
	Newznab is an nzbs.org clone PHP/Smarty application, which supports the indexing of 
	usenet headers into a mysql database and provides a simple web based search interface 
	onto the data.
	
	It includes simple CMS facilities, SEO friendly URLs and is designed with the intention 
	of allowing users to create a community around their index.
	
	For information on how to install, please refer to INSTALL.txt
	
	To discuss use irc.synirc.net #newznab
	
	Newznab is licensed under terms of the GNU General Public License.  For details, please 
	refer to LICENSE.txt.


HOW IT WORKS
	usenet groups are specified, message headers (binaries and parts) are downloaded for the 
	groups, releases are created from completed sets of binaries by applying regex to the message subject.
	releases are categorised by regexing the message subject. metadata from tvrage, tmdb, rotten tomatoes, 
	imdb and amazon are applied toeach created release. after a configurable number of days the header 
	data is deleted from the database, but the releases remain.
	
CHOOSING NEWSGROUPS
	groups can be manually entered if you know the name. groups can also be bulk added when
	specified as a regular expression. for example if you want to index the groups alt.bin.blah.* 
	and alt.bin.other use the value 'alt.bin.blah.*|alt.bin.other'. 
	
UPDATING INDEX (populating binaries + parts)
	the recommended way to schedule updates is via the dos and unix start scripts in 
	/path/to/newznab/misc/update_scripts/. make sure you set the paths correctly to your installation.
	
CATEGORISATION
	most categorisation of releases is done at the time of applying the regex. however if no category
	is supplied for a regex then \www\lib\category.php contains the logic which attempts to map a 
	release to a site category. site categories are used to make browsing nzbs easier. add new categories
	by updating the category table, and adding a new Category::constant. Then map it in the
	function determineCategory()

MISSING PARTS
	when headers are requested from the usenet provider, they are asked for in number ranges
	e.g. 1-1000, 1001-2000 etc. for various reasons sometimes the provider does not return 
	a header, this is not always because the header does not exist, there may be some synchronisation
	going on at the providers end. if a header is requested but not returned, we store a record of this
	in the table partrepair. each time update_binaries is ran an attempt is made to go back and get the 
	missing parts. if after five attempts the parts can still not be obtained, newznab gives up.
	when update_releases runs, if a release is seen to have missing parts it will not be released until
	four hours after it was uploaded to usenet. this is so a chance has been made to repair all missing
	parts. after four hours a release will be created anyway and its down to the quality of the par files
	to determine whether a release can be correctly unpacked.

BACKFILLING GROUPS
	since most usenet providers have 800+ days of retention indexing all that information in one shot
	is not practical. newznab provides a backfill feature that allow you to index past articles once
	your initial index has been built. to use the feature first set the back fill days setting in the group(s)
	to be backfilled to the number of day you wish to go back, making sure to set it higher than the number
	of days listed in the first post column. once set run the backfill.php script in misc/update_scripts.

REGEX MATCHING
	releases are created by applying regexs to binary message subjects. different regexes 
	are applied to binaries from different newsgroups. catchall regexes are applied to any
	binaries left unmatched after the group specific matching. a category can be associated
	with a regex, which will allow the processing of groups like inner-sanctum which contain a 
	combination of different binary types.
	
REGEX UPDATING
	regexes in the system in the range 0-10000 are system defined and are updated centrally.
	everytime processreleases is ran, a check will be performed to see if you have the latest regexs.
	if you do not want this check to be made then set site.latestregexurl to null
	
NZB FILE STORAGE
	nzbs are saved to disk gzipped at the location specified by site.nzbpath in subdirs based on the 
	first char of the release guid, this just makes the dirs a bit easier to manage when you have thousands
	of nzb.gz files. the default path is /website/../nzbfiles

SSL USENET CONNECTION
	Install the OpenSSL extension, set config.php define ('NNTP_SSLENABLED', true);
	
IMPORTING/EXPORTING NZBS
	.nzb files can be imported from the admin interface (or cli). importing is a convenient way to fill the
	index without trawling a large backdated number of usenet messages. after running an import 
	the processReleases() function must be run to create valid releases. nzbs can also be exported
	based on system categories.
	
GOOGLE ADS/ANALYTICS
	to integrate google analytics and adsense provide enter the adsense ad module ids 
	into the site table for the searchbox (bottom of menu). providing an analytics id will include 
	the analytics js in the footer
	
ADMIN
	admin functions all live in /admin/ which is only accessible by users with admin role.
	set users.role to be 2 on the users you wish to be admins

TVRAGE
	after releases.processReleases() is called, an attempt is made to determine the tvrageids for every 
	release	which looks like its tv. this also works out the series/episode columns. the data in the 
	tvrage table will become populated from best guesses from the rage search api. if some of these
	guesses are wrong, you can manually edit the rage data in the admin interface, and use the 
	remove link to wipe any releases which have that rageid and then manually call 'process tv' which
	will attempt to relink rage data. when a new release is created it goes in with a release.rageid of -1
	when tv is processed, the rageid either goes to the best guess, or to -2, which indicates no match 
	could be made, or the release isnt percieved to be tv.

TVRAGE - SERIES/EPISODE INFO
	information about a particular episode of a series is requested from services.tvrage.com a shared 
	newznab api key is used to retrieve this data. it assigns an 'aired date' to each release if a match
	is found.
	
NFO
	nfos are attempted to be retrieved using a queuing method. there will be a number of attempts to get
	an nfo before giving up
	
IMDB/TMDB/ROTTENTOMATOES
	if enabled, and if an imdb id is found in the nfo, the application will attempt to use that imdb id to
	get general data about the movie (title, year, genre, covers etc) from themoviedb.org. If no entry is 
	available from tmdb then an attempt to gather the info from imdb.com is made. any results are stored
	in the moveinfo table, with covers/backdrops being saved to the images/covers/.

3RD PARTY API KEYS
	in order to do lookups to tmdb, rotten tomatoes and amazon, api keys are used. newznab is shipped with 
	some default keys, but due to the restrictions on use of api's, it is strongly suggested you go and get 
	your own api keys and save them in the site edit page.

CONTENT/CMS
	pages can be added to the site with seo friendly urls via the /admin/ edit content links
	
SKINNING/THEMES
	avoid custom edits to code and stylesheets to make updating painless. 
	override any styles by creating a folder \www\theme\<yourtheme>\style.css. stick any custom images in
	\www\theme\<yourtheme>\images\
	pick the theme in the admin\site-edit page. your styles should override the existing style sheet, which
	should always be loaded in.
	
API
	www.sitename.com/api? provides api access to query and retrieve nzbs.
	call www.sitename.com/apihelp to see help doc with all available options. users either have to be 
	logged in or provide their rsstoken. users can use their rsstoken to access both rss + api.
	full details of the api and how to implement it are provided in /misc/api/
	
FAQS
	* authorisation rejected from nntp server - check you have enough available connections 
	not currently in use
	* white screen - probably php error not being displayed to browser or session timed out and 403 being throw
	* Lots of binary headers processed but few releases created - The binary headers downloaded do not match
	the regexes used to create a release. The message headers must follow popular formats in order for releases
	to be created.
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

