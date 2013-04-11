DROP TABLE IF EXISTS `feed`;
CREATE TABLE `feed` 
(
	`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`code` VARCHAR(50) NULL,
	`name` VARCHAR(255) NULL,
	`url` VARCHAR(1000) NOT NULL,
	`reqidcol` VARCHAR(255) NULL,
	`reqidregex` VARCHAR(2000) NOT NULL,
	`titlecol` VARCHAR(255) NULL,
	`titleregex` VARCHAR(2000) NOT NULL,
	`lastupdate` DATETIME NULL,
	`updatemins` TINYINT(3) NOT NULL DEFAULT  '55',
	`status` INT NOT NULL DEFAULT 1,
	PRIMARY KEY  (`ID`)
) ENGINE=MYISAM AUTO_INCREMENT=1 ;

CREATE INDEX ix_feed_code ON feed (code);

insert into feed (code, name, url, titlecol, titleregex, reqidcol, reqidregex, lastupdate) values ('alt.binaries.teevee', 'abteevee', 'http://abteevee.allfilled.com/rss.php', 'title', '/(?P<title>.*)/i', 'description', '/^ReqId: (?P<reqid>\\d{3,6})/i', null);
insert into feed (code, name, url, titlecol, titleregex, reqidcol, reqidregex, lastupdate) values ('alt.binaries.erotica', 'aberotica', 'http://aberotica.allfilled.com/rss.php', 'title', '/(?P<title>.*)/i', 'description', '/^ReqId: (?P<reqid>\\d{3,6})/i', null);
insert into feed (code, name, url, titlecol, titleregex, reqidcol, reqidregex, lastupdate) values ('alt.binaries.games.wii', 'abgwii', 'http://www.abgx.net/rss/abgw/posted.rss', 'title', '/^Req\\s\\d{1,6}\\s\\-\\s(?P<title>.\\S*)/i', 'title', '/^Req (?P<reqid>\\d{3,6})/i', null);
insert into feed (code, name, url, titlecol, titleregex, reqidcol, reqidregex, lastupdate) values ('alt.binaries.games.xbox360', 'abg360', 'http://www.abgx.net/rss/x360/posted.rss', 'title', '/^Req\\s\\d{1,6}\\s\\-\\s(?P<title>.\\S*)/i', 'title', '/^Req (?P<reqid>\\d{3,6})/i', null);
insert into feed (code, name, url, titlecol, titleregex, reqidcol, reqidregex, lastupdate) values ('alt.binaries.console.ps3', 'ps3', 'http://www.abgx.net/rss/abcp/posted.rss', 'title', '/^Req\\s\\d{1,6}\\s\\-\\s(?P<title>.\\S*)/i', 'title', '/^Req (?P<reqid>\\d{3,6})/i', null);



DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` 
(
	`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`feedID` INT NOT NULL,
	`reqid` INT NOT NULL,
	`title` VARCHAR(255) NULL,
	`link` VARCHAR(1000) NULL,
	`description` VARCHAR(1000) NULL,
	`pubdate` DATETIME NOT NULL,
	`guid` VARCHAR(50) NULL,
	`adddate` DATETIME NOT NULL,
	PRIMARY KEY  (`ID`)
) ENGINE=MYISAM AUTO_INCREMENT=1 ;

CREATE INDEX ix_item_feedID ON item (feedID);
CREATE INDEX ix_item_reqid ON item (reqid);
CREATE UNIQUE INDEX ix_reqid_title ON item (reqid, title);
