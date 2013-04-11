ALTER TABLE  `site` ADD  `lookupgames` INT NOT NULL DEFAULT  '1' AFTER  `lookupmusic` ;

ALTER TABLE `releases` ADD `consoleinfoID` INT NULL AFTER  `musicinfoID`;

CREATE TABLE `consoleinfo` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `asin` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salesrank` int(10) unsigned DEFAULT NULL,
  `platform` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `releasedate` datetime DEFAULT NULL,
  `review` varchar(2000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cover` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `createddate` datetime NOT NULL,
  `updateddate` datetime NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
