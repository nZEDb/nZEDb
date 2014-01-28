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


