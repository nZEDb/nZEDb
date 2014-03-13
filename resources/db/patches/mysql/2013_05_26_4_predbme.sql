DROP TABLE IF EXISTS `predbme`;
CREATE TABLE `predbme`
(
`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL DEFAULT '',
`adddate` DATETIME DEFAULT NULL,
PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT IGNORE INTO `predbme` (`title`, `adddate`) VALUES ('FIRST_CHECK_DO_NOT_DELETE_THIS', '2013-05-26 10:07:25');

CREATE INDEX ix_predbme_title ON predbme(`title`);
CREATE INDEX ix_predbme_adddate ON predbme(`adddate`);

UPDATE `site` set `value` = '55' where `setting` = 'sqlpatch';
