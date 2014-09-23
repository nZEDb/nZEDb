DROP TABLE IF EXISTS collections;
CREATE TABLE collections (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	subject VARCHAR(255) NOT NULL DEFAULT '',
	fromname VARCHAR(255) NOT NULL DEFAULT '',
	date DATETIME DEFAULT NULL,
	xref VARCHAR(255) NOT NULL DEFAULT '',
	totalfiles INT(11) UNSIGNED NOT NULL DEFAULT '0',
	group_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
	collectionhash VARCHAR(255) NOT NULL DEFAULT '0',
	dateadded DATETIME DEFAULT NULL,
	filecheck TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	filesize BIGINT UNSIGNED NOT NULL DEFAULT '0',
	releaseid INT NULL,
	PRIMARY KEY (id),
	KEY fromname (fromname),
	KEY date (date),
	KEY groupid (groupid)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_collection_filecheck ON collections (filecheck);
CREATE INDEX ix_collection_dateadded ON collections (dateadded);
CREATE UNIQUE INDEX ix_collection_collectionhash ON collections (collectionhash);
CREATE INDEX ix_collection_releaseid ON collections (releaseid);

DROP TABLE IF EXISTS binaries;
CREATE TABLE binaries (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(1000) NOT NULL DEFAULT '',
	collectionid INT(11) UNSIGNED NOT NULL DEFAULT '0',
	filenumber INT UNSIGNED NOT NULL DEFAULT '0',
	totalparts INT(11) UNSIGNED NOT NULL DEFAULT '0',
	binaryhash VARCHAR(255) NOT NULL DEFAULT '0',
	partcheck BIT NOT NULL DEFAULT 0,
	partsize BIGINT UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_binary_binaryhash ON binaries (binaryhash);
CREATE INDEX ix_binary_partcheck ON binaries (partcheck);
CREATE INDEX ix_binary_collection ON binaries (collectionid);

DROP TABLE IF EXISTS releases;
CREATE TABLE releases (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT '',
	searchname VARCHAR(255) NOT NULL DEFAULT '',
	totalpart INT DEFAULT 0,
	groupid INT UNSIGNED NOT NULL DEFAULT '0',
	size BIGINT UNSIGNED NOT NULL DEFAULT '0',
	postdate DATETIME DEFAULT NULL,
	adddate DATETIME DEFAULT NULL,
	updatetime TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	guid VARCHAR(50) NOT NULL,
	fromname VARCHAR(255) NULL,
	completion FLOAT NOT NULL DEFAULT '0',
	categoryid INT NOT NULL DEFAULT 7010,
	rageid INT NULL,
	seriesfull VARCHAR(15) NULL,
	season VARCHAR(10) NULL,
	episode VARCHAR(10) NULL,
	tvtitle varchar(255) NULL,
	tvairdate datetime NULL,
	imdbid MEDIUMINT(7) UNSIGNED ZEROFILL NULL,
	musicinfoid INT NULL,
	consoleinfoid INT NULL,
	bookinfoid INT NULL,
	anidbid INT NULL,
	preid INT UNSIGNED NOT NULL DEFAULT '0',
	grabs INT UNSIGNED NOT NULL DEFAULT '0',
	comments INT NOT NULL DEFAULT 0,
	passwordstatus TINYINT NOT NULL DEFAULT 0,
	rarinnerfilecount INT NOT NULL DEFAULT 0,
	haspreview TINYINT NOT NULL DEFAULT 0,
	nfostatus TINYINT NOT NULL DEFAULT 0,
	jpgstatus BIT NOT NULL DEFAULT 0,
	videostatus BIT NOT NULL DEFAULT 0,
	audiostatus BIT NOT NULL DEFAULT 0,
	dehashstatus TINYINT(1) NOT NULL DEFAULT 0,
	reqidstatus TINYINT(1) NOT NULL DEFAULT '0',
	nzb_guid VARCHAR(50) NULL,
	nzbstatus BIT NOT NULL DEFAULT 0,
	iscategorized BIT NOT NULL DEFAULT 0,
	isrenamed BIT NOT NULL DEFAULT 0,
	ishashed BIT NOT NULL DEFAULT 0,
	isrequestid BIT NOT NULL DEFAULT 0,
	proc_pp BIT NOT NULL DEFAULT 0,
	proc_sorter BIT NOT NULL DEFAULT 0,
	proc_par2 BIT NOT NULL DEFAULT 0,
	proc_nfo BIT NOT NULL DEFAULT 0,
	proc_files BIT NOT NULL DEFAULT 0,
	PRIMARY KEY (id, categoryid)
	) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1
	PARTITION BY RANGE (categoryid) (
	PARTITION unused VALUES LESS THAN (1000),
	PARTITION console VALUES LESS THAN (2000),
	PARTITION movies VALUES LESS THAN (3000),
	PARTITION audio VALUES LESS THAN (4000),
	PARTITION pc VALUES LESS THAN (5000),
	PARTITION tv VALUES LESS THAN (6000),
	PARTITION xxx VALUES LESS THAN (7000),
	PARTITION misc VALUES LESS THAN (8000),
	PARTITION books VALUES LESS THAN (9000)
	) ;

CREATE INDEX ix_releases_adddate ON releases (adddate);
CREATE INDEX ix_releases_rageid ON releases (rageid);
CREATE INDEX ix_releases_imdbid ON releases (imdbid);
CREATE INDEX ix_releases_guid ON releases (guid);
CREATE INDEX ix_releases_name ON releases (name);
CREATE INDEX ix_releases_groupid ON releases (groupid);
CREATE INDEX ix_releases_dehashstatus ON releases (dehashstatus);
CREATE INDEX ix_releases_reqidstatus ON releases (reqidstatus);
CREATE INDEX ix_releases_nfostatus ON releases (nfostatus);
CREATE INDEX ix_releases_musicinfoid ON releases (musicinfoid);
CREATE INDEX ix_releases_consoleinfoid ON releases (consoleinfoid);
CREATE INDEX ix_releases_bookinfoid ON releases (bookinfoid);
CREATE INDEX ix_releases_haspreview_passwordstatus ON releases (haspreview, passwordstatus);
CREATE INDEX ix_releases_status ON releases (nzbstatus, iscategorized, isrenamed, nfostatus, ishashed, isrequestid, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid, imdbid, rageid);
CREATE INDEX ix_releases_postdate_searchname ON releases (postdate, searchname);
CREATE INDEX ix_releases_nzb_guid ON releases (nzb_guid);
CREATE INDEX ix_releases_preid_searchname ON releases (preid, searchname);

DROP TABLE IF EXISTS releasefiles;
CREATE TABLE releasefiles (
	id INT(10) NOT NULL AUTO_INCREMENT,
	releaseid INT(11) UNSIGNED NOT NULL,
	name VARCHAR(255) COLLATE utf8_unicode_ci NULL,
	size BIGINT UNSIGNED NOT NULL DEFAULT '0',
	createddate DATETIME DEFAULT NULL,
	passworded TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_releasefiles_name_releaseid ON releasefiles (name, releaseid);
CREATE INDEX ix_releasefiles_releaseid ON releasefiles (releaseid);
CREATE INDEX ix_releasefiles_name ON releasefiles (name);

DROP TABLE IF EXISTS releasevideo;
CREATE TABLE releasevideo (
	releaseid int(11) unsigned NOT NULL,
	containerformat varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	overallbitrate varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
	videoduration varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
	videoformat varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	videocodec varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	videowidth int(10) DEFAULT NULL,
	videoheight int(10) DEFAULT NULL,
	videoaspect varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
	videoframerate float(7,4) DEFAULT NULL,
	videolibrary varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (releaseid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS releaseaudio;
CREATE TABLE releaseaudio (
	id int(11) unsigned auto_increment,
	releaseid int(11) unsigned NOT NULL,
	audioid int(2) unsigned NOT NULL,
	audioformat varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiomode varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiobitratemode varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiobitrate varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiochannels varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiosamplerate varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiolibrary varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiolanguage varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	audiotitle varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX ix_releaseaudio_releaseid_audioid ON releaseaudio (releaseid, audioid);

DROP TABLE IF EXISTS releasesubs;
CREATE TABLE releasesubs (
	id int(11) unsigned auto_increment,
	releaseid int(11) unsigned NOT NULL,
	subsid int(2) unsigned NOT NULL,
	subslanguage varchar(50) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX ix_releasesubs_releaseid_subsid ON releasesubs (releaseid,subsid);

DROP TABLE IF EXISTS releaseextrafull;
CREATE TABLE releaseextrafull (
	releaseid INT(11) UNSIGNED NOT NULL,
	mediainfo TEXT NULL,
	PRIMARY KEY (releaseid)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS releasecomment;
CREATE TABLE releasecomment (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	releaseid INT(11) UNSIGNED NOT NULL,
	text VARCHAR(2000) NOT NULL DEFAULT '',
	user_id INT(11) UNSIGNED NOT NULL,
	createddate DATETIME DEFAULT NULL,
	host VARCHAR(15) NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_releasecomment_releaseid ON releasecomment (releaseid);
CREATE INDEX ix_releasecomment_userid ON releasecomment (user_id);

DROP TABLE IF EXISTS predb;
CREATE TABLE predb (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL DEFAULT '',
	nfo VARCHAR(255) NULL,
	size VARCHAR(50) NULL,
	category VARCHAR(255) NULL,
	predate DATETIME DEFAULT NULL,
	adddate DATETIME DEFAULT NULL,
	source VARCHAR(50) NOT NULL DEFAULT '',
	md5 VARCHAR(255) NOT NULL DEFAULT '0',
	requestid INT(10) UNSIGNED NOT NULL DEFAULT '0',
	groupid INT(10) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_predb_title ON predb (title);
CREATE INDEX ix_predb_nfo ON predb (nfo);
CREATE INDEX ix_predb_predate ON predb (predate);
CREATE INDEX ix_predb_adddate ON predb (adddate);
CREATE INDEX ix_predb_source ON predb (source);
CREATE INDEX ix_predb_requestid on predb (requestid, groupid);
CREATE UNIQUE INDEX ix_predb_md5 ON predb (md5);

DROP TABLE IF EXISTS menu;
CREATE TABLE menu (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	href VARCHAR(2000) NOT NULL DEFAULT '',
	title VARCHAR(2000) NOT NULL DEFAULT '',
	newwindow int(1) unsigned NOT NULL DEFAULT 0,
	tooltip VARCHAR(2000) NOT NULL DEFAULT '',
	role INT(11) UNSIGNED NOT NULL,
	ordinal INT(11) UNSIGNED NOT NULL,
	menueval VARCHAR(2000) NOT NULL DEFAULT '',
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS releasenfo;
CREATE TABLE releasenfo (
	id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	releaseid int(11) UNSIGNED NOT NULL,
	nfo BLOB NULL DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_releasenfo_releaseid ON releasenfo (releaseid);

DROP TABLE IF EXISTS binaryblacklist;
CREATE TABLE binaryblacklist (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	groupname VARCHAR(255) NULL,
	regex VARCHAR(2000) NOT NULL,
	msgcol INT(11) UNSIGNED NOT NULL DEFAULT 1,
	optype INT(11) UNSIGNED NOT NULL DEFAULT 1,
	status INT(11) UNSIGNED NOT NULL DEFAULT 1,
	description VARCHAR(1000) NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=100000;

CREATE INDEX ix_binaryblacklist_groupname ON binaryblacklist (groupname);
CREATE INDEX ix_binaryblacklist_status ON binaryblacklist (status);

DROP TABLE IF EXISTS tvrage;
CREATE TABLE tvrage (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	rageid INT NOT NULL,
	tvdbid INT NOT NULL DEFAULT  '0',
	releasetitle VARCHAR(255) NOT NULL DEFAULT '',
	description VARCHAR(10000) NULL,
	genre VARCHAR(64) NULL DEFAULT NULL,
	country VARCHAR(2) NULL DEFAULT NULL,
	imgdata longblob NULL,
	createddate DATETIME DEFAULT NULL,
	prevdate DATETIME NULL,
	previnfo VARCHAR( 255 ) NULL,
	nextdate DATETIME NULL,
	nextinfo VARCHAR( 255 ) NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_tvrage_rageid_releasetitle ON tvrage (rageid, releasetitle);
CREATE INDEX ix_tvrage_rageid ON tvrage (rageid);
CREATE INDEX ix_tvrage_releasetitle ON tvrage (releasetitle);

DROP TABLE IF EXISTS forumpost;
CREATE TABLE IF NOT EXISTS forumpost (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	forumid INT(11) NOT NULL DEFAULT '1',
	parentid INT(11) NOT NULL DEFAULT '0',
	user_id INT(11) UNSIGNED NOT NULL,
	subject VARCHAR(255) NOT NULL,
	message TEXT NOT NULL,
	locked TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	sticky TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	replies INT(11) UNSIGNED NOT NULL DEFAULT '0',
	createddate DATETIME NOT NULL,
	updateddate DATETIME NOT NULL,
	PRIMARY KEY (id),
	KEY parentid (parentid),
	KEY userid (user_id),
	KEY createddate (createddate),
	KEY updateddate (updateddate)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;


DROP TABLE IF EXISTS movieinfo;
CREATE TABLE movieinfo (
 id int(10) unsigned NOT NULL AUTO_INCREMENT,
	imdbid mediumint(7) unsigned zerofill NOT NULL,
	tmdbid int(10) unsigned DEFAULT NULL,
	title varchar(255) NOT NULL,
	tagline VARCHAR(1024) NOT NULL,
	rating varchar(4) NOT NULL,
	plot varchar(1024) NOT NULL,
	year varchar(4) NOT NULL,
	genre varchar(64) NOT NULL,
	type varchar(32) NOT NULL,
	director VARCHAR(64) NOT NULL,
	actors VARCHAR(2000) NOT NULL,
	language VARCHAR(64) NOT NULL,
	cover TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	backdrop TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	createddate datetime NOT NULL,
	updateddate datetime NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_movieinfo_title ON movieinfo (title);
CREATE UNIQUE INDEX ix_movieinfo_imdbid ON movieinfo (imdbid);

DROP TABLE IF EXISTS animetitles;
CREATE TABLE animetitles (
	anidbid INT(7) UNSIGNED NOT NULL,
	title VARCHAR(255) NOT NULL,
	unixtime INT(12) UNSIGNED NOT NULL,
	PRIMARY KEY (title)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


DROP TABLE IF EXISTS anidb;
CREATE TABLE anidb (
	anidbid INT(7) UNSIGNED NOT NULL,
	imdbid INT(7) UNSIGNED NOT NULL,
	tvdbid INT(7) UNSIGNED NOT NULL,
	title VARCHAR(255) NOT NULL,
	type VARCHAR(32) NOT NULL,
	startdate DATE DEFAULT NULL,
	enddate DATE DEFAULT NULL,
	related VARCHAR(1024) NOT NULL,
	creators VARCHAR(1024) NOT NULL,
	description TEXT NOT NULL,
	rating VARCHAR(5) NOT NULL,
	picture VARCHAR(16) NOT NULL,
	categories VARCHAR(1024) NOT NULL,
	characters VARCHAR(1024) NOT NULL,
	epnos VARCHAR(2048) NOT NULL,
	airdates TEXT NOT NULL,
	episodetitles TEXT NOT NULL,
	unixtime INT(12) UNSIGNED NOT NULL,
	PRIMARY KEY (anidbid)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;


DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT '',
	backfill_target INT(4) NOT NULL DEFAULT '1',
	first_record BIGINT UNSIGNED NOT NULL DEFAULT '0',
	first_record_postdate DATETIME DEFAULT NULL,
	last_record BIGINT UNSIGNED NOT NULL DEFAULT '0',
	last_record_postdate DATETIME DEFAULT NULL,
	last_updated DATETIME DEFAULT NULL,
	minfilestoformrelease INT(4) NULL,
	minsizetoformrelease BIGINT NULL,
	active TINYINT(1) NOT NULL DEFAULT '0',
	backfill TINYINT(1) NOT NULL DEFAULT '0',
	description VARCHAR(255) NULL DEFAULT '',
	PRIMARY KEY (id),
	KEY active (active)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_groups_id ON groups (id);
CREATE UNIQUE INDEX ix_groups_name ON groups (name);

DROP TABLE IF EXISTS parts;
CREATE TABLE parts (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	binaryid BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
	messageid VARCHAR(255) NOT NULL DEFAULT '',
	number BIGINT UNSIGNED NOT NULL DEFAULT '0',
	partnumber INT UNSIGNED NOT NULL DEFAULT '0',
	size BIGINT UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	KEY binaryid (binaryid)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_parts_number ON parts (number);
CREATE UNIQUE INDEX ix_parts_messageid ON parts (messageid);

DROP TABLE IF EXISTS partrepair;
CREATE TABLE partrepair (
	id int(16) unsigned NOT NULL AUTO_INCREMENT,
	numberid BIGINT unsigned NOT NULL,
	groupid int(11) unsigned NOT NULL,
	attempts tinyint(1) NOT NULL default '0',
	PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_partrepair_numberid_groupid ON partrepair (numberid, groupid);
CREATE INDEX ix_partrepair_attempts ON partrepair (attempts);
CREATE INDEX ix_partrepair_groupid_attempts ON partrepair (groupid,attempts);
CREATE INDEX ix_partrepair_numberid_groupid_attempts ON partrepair (numberid,groupid,attempts);

DROP TABLE IF EXISTS category;
CREATE TABLE category (
	id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL,
	parentid INT NULL,
	status INT NOT NULL DEFAULT '1',
	description varchar(255) NULL,
	disablepreview tinyint(1) NOT NULL default '0',
	minsize BIGINT UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=100000;

CREATE INDEX ix_category_status ON category (status);
CREATE INDEX ix_category_parentid ON category (parentid);

DROP TABLE IF EXISTS users;
CREATE TABLE users (
	id INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
	username VARCHAR(50) NOT NULL,
	firstname VARCHAR(255) DEFAULT NULL,
	lastname VARCHAR(255) DEFAULT NULL,
	email VARCHAR(255) NOT NULL,
	password VARCHAR(255) NOT NULL,
	role INT NOT NULL DEFAULT 1,
	host VARCHAR(40) NULL,
	grabs INT NOT NULL DEFAULT 0,
	rsstoken VARCHAR(32) NOT NULL,
	createddate DATETIME NOT NULL,
	resetguid VARCHAR(50) NULL,
	lastlogin datetime default NULL,
	apiaccess datetime default NULL,
	invites int NOT NULL default 0,
	invitedby int NULL,
	movieview int NOT NULL default 1,
	musicview int NOT NULL default 1,
	consoleview int NOT NULL default 1,
	bookview int NOT NULL default 1,
	saburl VARCHAR(255) NULL DEFAULT NULL,
	sabapikey VARCHAR(255) NULL DEFAULT NULL,
	sabapikeytype TINYINT(1) NULL DEFAULT NULL,
	sabpriority TINYINT(1) NULL DEFAULT NULL,
	userseed VARCHAR(50) NOT NULL,
	cp_url VARCHAR(255) NULL DEFAULT NULL,
	cp_api VARCHAR(255) NULL DEFAULT NULL,
	style VARCHAR(255) NULL DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS userseries;
CREATE TABLE userseries (
	id INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id int(16) NOT NULL,
	rageid int(16) NOT NULL,
	categoryid VARCHAR(64) NULL DEFAULT NULL,
	createddate DATETIME NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_userseries_userid ON userseries (user_id, rageid);

DROP TABLE IF EXISTS usermovies;
CREATE TABLE usermovies (
	id INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id int(16) NOT NULL,
	imdbid MEDIUMINT(7) UNSIGNED ZEROFILL NULL,
	categoryid VARCHAR(64) NULL DEFAULT NULL,
	createddate DATETIME NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_usermovies_userid ON usermovies (user_id, imdbid);

DROP TABLE IF EXISTS tvrageepisodes;
CREATE TABLE tvrageepisodes (
	id int(11) unsigned NOT NULL AUTO_INCREMENT,
	rageid int(11) unsigned NOT NULL,
	showtitle VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	airdate datetime NOT NULL,
	link varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	fullep varchar(20) COLLATE utf8_unicode_ci NOT NULL,
	eptitle varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX ix_tvrageepisodes_rageid ON tvrageepisodes (rageid, fullep);

DROP TABLE IF EXISTS userroles;
CREATE TABLE userroles (
	id int(10) NOT NULL AUTO_INCREMENT,
	name varchar(32) COLLATE utf8_unicode_ci NOT NULL,
	apirequests int(10) unsigned NOT NULL,
	downloadrequests int(10) unsigned NOT NULL,
	defaultinvites int(10) unsigned NOT NULL,
	isdefault tinyint(1) unsigned NOT NULL DEFAULT 0,
	canpreview  tinyint(1) unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4;


DROP TABLE IF EXISTS userrequests;
CREATE TABLE userrequests (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	user_id int(16) NOT NULL,
	request varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	timestamp datetime NOT NULL,
	PRIMARY KEY (id),
	KEY userid (user_id),
	KEY timestamp (timestamp)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS userdownloads;
CREATE TABLE userdownloads (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	user_id int(16) NOT NULL,
	timestamp datetime NOT NULL,
	PRIMARY KEY (id),
	KEY userid (user_id),
	KEY timestamp (timestamp)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS usercart;
CREATE TABLE usercart (
	id INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT NOT NULL ,
	releaseid INT NOT NULL,
	createddate DATETIME NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_usercart_userrelease ON usercart (user_id, releaseid);

DROP TABLE IF EXISTS userexcat;
CREATE TABLE userexcat (
	id INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT NOT NULL ,
	categoryid INT NOT NULL,
	createddate DATETIME NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_userexcat_usercat ON userexcat (user_id, categoryid);

DROP TABLE IF EXISTS userinvite;
CREATE TABLE userinvite (
	id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	guid varchar(50) NOT NULL,
	user_id int(11) UNSIGNED NOT NULL,
	createddate datetime NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS content;
	CREATE TABLE content (
	id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL,
	url VARCHAR(2000) NULL,
	body TEXT NULL,
	metadescription VARCHAR(1000) NOT NULL,
	metakeywords VARCHAR(1000) NOT NULL,
	contenttype INT NOT NULL,
	showinmenu INT NOT NULL,
	status INT NOT NULL,
	ordinal INT NULL,
	role INT NOT NULL DEFAULT 0
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS site;
CREATE TABLE site (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	setting VARCHAR(64) NOT NULL,
	value VARCHAR(19000) NULL,
	updateddate TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_site_setting ON site (setting);

DROP TABLE IF EXISTS logging;
CREATE TABLE logging (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	time datetime DEFAULT NULL,
	username varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
	host varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;


DROP TABLE IF EXISTS consoleinfo;
CREATE TABLE consoleinfo (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	asin varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
	url varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
	salesrank int(10) unsigned DEFAULT NULL,
	platform varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	publisher varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	genreid INT( 10 ) NULL DEFAULT NULL,
	esrb VARCHAR( 255 ) NULL DEFAULT NULL,
	releasedate datetime DEFAULT NULL,
	review varchar(3000) COLLATE utf8_unicode_ci DEFAULT NULL,
	cover tinyint(1) unsigned NOT NULL DEFAULT '0',
	createddate datetime NOT NULL,
	updateddate datetime NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_consoleinfo_asin ON consoleinfo (asin);

DROP TABLE IF EXISTS bookinfo;
CREATE TABLE bookinfo (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	author varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	asin varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
	isbn varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
	ean varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
	url varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
	salesrank int(10) unsigned DEFAULT NULL,
	publisher varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	publishdate datetime DEFAULT NULL,
	pages varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
	overview varchar(3000) COLLATE utf8_unicode_ci DEFAULT NULL,
	genre varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	cover tinyint(1) unsigned NOT NULL DEFAULT '0',
	createddate datetime NOT NULL,
	updateddate datetime NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_bookinfo_asin ON bookinfo (asin);

DROP TABLE IF EXISTS musicinfo;
CREATE TABLE musicinfo (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	title varchar(255) NOT NULL,
	asin varchar(128) NULL,
	url varchar(1000) NULL,
	salesrank int(10) unsigned NULL,
	artist varchar(255) NULL,
	publisher varchar(255) NULL,
	releasedate datetime NULL,
	review varchar(3000) NULL,
	year varchar(4) NOT NULL,
	genreid int(10) NULL,
	tracks varchar(3000) NULL,
	cover TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	createddate datetime NOT NULL,
	updateddate datetime NOT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_musicinfo_asin ON musicinfo (asin);

DROP TABLE IF EXISTS upcoming;
CREATE TABLE upcoming (
	id INT(10) NOT NULL AUTO_INCREMENT,
	source VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
	typeid INT(10) NOT NULL,
	info TEXT NULL,
	updateddate TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE UNIQUE INDEX ix_upcoming_source ON upcoming (source, typeid);

DROP TABLE IF EXISTS genres;
CREATE TABLE genres (
	id int NOT NULL AUTO_INCREMENT,
	title varchar(255) NOT NULL,
	type INT( 4 ) NULL DEFAULT NULL,
	disabled tinyint(1) NOT NULL default '0',
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

DROP TABLE IF EXISTS tmux;
CREATE TABLE tmux (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	setting varchar(64) COLLATE utf8_unicode_ci NOT NULL,
	value varchar(19000) COLLATE utf8_unicode_ci DEFAULT NULL,
	updateddate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=MyIsam DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX ix_tmux_setting ON tmux (setting);

DROP TABLE IF EXISTS nzbs;
CREATE TABLE nzbs (
	id int(10) unsigned NOT NULL AUTO_INCREMENT,
	message_id varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
	groupname varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
	subject varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
	collectionhash varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
	filesize bigint(20) unsigned NOT NULL DEFAULT '0',
	partnumber int(10) unsigned NOT NULL DEFAULT '0',
	totalparts int(10) unsigned NOT NULL DEFAULT '0',
	postdate datetime DEFAULT NULL,
	dateadded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=MyIsam DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_nzbs_partnumber ON nzbs (partnumber);
CREATE INDEX ix_nzbs_collectionhash ON nzbs (collectionhash);
CREATE UNIQUE INDEX ix_nzbs_message ON nzbs (message_id);

DROP TABLE IF EXISTS allgroups;
CREATE TABLE allgroups (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT "",
	first_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
	last_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
	updated DATETIME DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_allgroups_id ON allgroups(id);
CREATE INDEX ix_allgroups_name ON allgroups(name);

DROP TABLE IF EXISTS shortgroups;
CREATE TABLE shortgroups (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT "",
	first_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
	last_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
	updated DATETIME DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_shortgroups_id ON shortgroups(id);
CREATE INDEX ix_shortgroups_name ON shortgroups(name);

DROP TABLE IF EXISTS countries;
CREATE TABLE countries (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT "",
	code CHAR(2) NOT NULL DEFAULT "",
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_country_name ON country (name);


DELIMITER $$
CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.isrequestid = 1; END IF; END; $$
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.isrequestid = 1; END IF; END; $$
DELIMITER ;
