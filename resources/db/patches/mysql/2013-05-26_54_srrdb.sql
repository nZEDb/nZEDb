DROP TABLE IF EXISTS `srrdb`;
CREATE TABLE `srrdb`
(
`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL DEFAULT '',
`pubDate` DATETIME DEFAULT NULL,
`adddate` DATETIME DEFAULT NULL,
PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT IGNORE INTO `srrdb` (`title`, `pubDate`, `adddate`) VALUES ('FIRST_CHECK_DO_NOT_DELETE_THIS', '2013-05-26 10:07:25', '2013-05-26 10:07:25');

CREATE INDEX ix_srrdb_title ON srrdb(`title`);
CREATE INDEX ix_srrdb_pubDate ON srrdb(`pubDate`);
CREATE INDEX ix_srrdb_adddate ON srrdb(`adddate`);

UPDATE `site` set `value` = '54' where `setting` = 'sqlpatch';
