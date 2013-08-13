DROP TABLE IF EXISTS `nzbs`;
CREATE TABLE `nzbs` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `group` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `article-number` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `collectionhash` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `filesize` bigint(20) unsigned NOT NULL DEFAULT '0',
  `partnumber` int(10) unsigned NOT NULL DEFAULT '0',
  `totalparts` int(10) unsigned NOT NULL DEFAULT '0',
  `postdate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyIsam DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

UPDATE `site` set `value` = '74' where `setting` = 'sqlpatch';
