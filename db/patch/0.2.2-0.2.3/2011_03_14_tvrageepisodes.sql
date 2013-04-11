DROP TABLE IF EXISTS `tvrageepisodes`;
CREATE TABLE `tvrageepisodes` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tvrageID` int(11) unsigned NOT NULL,
  `airdate` datetime NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullep` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `tvrageID` (`tvrageID`,`fullep`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
