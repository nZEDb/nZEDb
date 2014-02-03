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

As a general rule of thumb the database will need a minimum of 1-2G buffer RAM for every million releases you intend to store. That RAM should be assigned to these two parameters:
- key_buffer_size			(MyISAM)
- innodb_buffer_pool_size	(InnoDB)

Use [mysqltuner.pl](http://mysqltuner.pl "MySQL tuner - Use it!") for recommendations for these and other important tuner parameters. Also refer to the project's wiki page: https://github.com/nZEDb/nZEDb/wiki/Database-tuning. This is particularly import before you start any large imports or backfills.

If you wish to use more than 5 threads a quad core CPU is beneficial.

### Software

	PHP 5.4+ (and various modules)
    MySQL 5.5+ (Postgres support is Work-In-Progress)
    Python 2.7 or 3.0 (and various modules)
The installation guides have more detailed software requirements.

## Installation

Specific installation guides for common Operating Systems can be found on the nZEDb github wiki: https://github.com/nZEDb/nZEDb/wiki/Install-Guides

## Getting Started

After you have installed nZEDb and gone throught the initial web-based installation steps (step1, step2 etc.), then review the settings on the Admin-Site-Edit page.

Most of the default settings are fine, however some will need changing.

The two amazon keys and the associate tag are needed to fetch anything from amazon. The trakt.tv key is optional, but it can help fetching extra information when tvrage and the NFO fails.

Setting the paths to unrar/ffmpeg/mediainfo is optional, but unrar is recommended for retrieving release names and finding passwords. It's best to get the very latest versions of these utilities, whatever comes as default with your distro is often not good enough.

If you have set the path to unrar, deep rar inspection is recommended.

Compressed headers are recommended if your provider supports XFeature gzip compression, however header retrieval may periodically hang if the nntp-proxy is not used. (XFeature GZIP compression, originally by wafflehouse : http://pastebin.com/A3YypDAJ)

Once you have set all the options, you can enable one or two groups and start with the simple screen script running in single-threaded mode. Look in the misc/update directory; update_binaries.php downloads usenet articles into the local database; update_releases.php attempts to group these articles into releases and create NZB files.

When you've become more familiar with the application, enable a few more groups and if needed enable multi-threading (with low thread counts i.e. < 5). We do not recommend enabling all the groups unless you have performant hardware and good database tuning knowledge.

If you want an automated way of doing this, you can use one of the scripts in the nix, or tmux folder. The Windows scripts may work.

To clean up the release names, check out fixReleaseNames.php in misc/testing.

For an overview of a complete process, look at the  misc/update/nix/screen/sequential/threaded.sh script.

Advanced users may be interested to try the tmux version of nZEDb in either Complete Sequential, Sequential or non-Sequential (fully parallel) mode. Before starting, review the tmux settings in Site-Edit -> Tmux Settings.


### Support

There is a web forum were you can search for issues previously encountered by others:
http://nzedb.com/

Also on IRC: irc.synirc.net #nZEDb

### Note

The nZEDb team have no control over and are not responsible for what is posted on the usenet. Best efforts are made to avoid hazardous content (e.g. virii, malware etc) by nZEDb's automated processess. If you find any objectionable content, please direct any complaints to your usenet provider.

### The Team

Kevin123, jonnyboy, Miatrix, zombu2, Codeslave, sinfuljosh, ugo, Whitelighter, Bart39 and archer (niel)<br /><br />
Paypal: <a href="http://nzedb.com/index.php?action=treasury"><img src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" alt="PayPal - The safer, easier way to pay online!" /></a>

Bitcoin wallet: 1LrrFbXn4QfGUokLppHVPQHAzmnAPbgV2M

<p>
