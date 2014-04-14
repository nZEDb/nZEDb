DROP TABLE IF EXISTS `predbme`;
DROP TABLE IF EXISTS `srrdb`;
DROP TABLE IF EXISTS `predb`;
CREATE TABLE `predb`
(
`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL DEFAULT '',
`nfo` VARCHAR(255) NULL,
`size` VARCHAR(50) NULL,
`category` VARCHAR(255) NULL,
`predate` DATETIME DEFAULT NULL,
`adddate` DATETIME DEFAULT NULL,
`source` VARCHAR(50) NOT NULL DEFAULT '',
`md5` VARCHAR(255) NOT NULL DEFAULT '0',
PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_predb_title ON predb(`title`);
CREATE INDEX ix_predb_nfo ON predb(`nfo`);
CREATE INDEX ix_predb_predate ON predb(`predate`);
CREATE INDEX ix_predb_adddate ON predb(`adddate`);
CREATE INDEX ix_predb_source ON predb(`source`);
CREATE INDEX ix_predb_md5 ON predb(`md5`);

UPDATE `site` set `value` = '57' where `setting` = 'sqlpatch';
