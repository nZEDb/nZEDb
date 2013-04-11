DROP TABLE IF EXISTS `releaseextra`;
DROP TABLE IF EXISTS `releasevideo`;
CREATE TABLE `releasevideo` (
  `releaseID` int(11) unsigned NOT NULL,
  `containerformat` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `overallbitrate` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `videoduration` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `videoformat` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `videocodec` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `videowidth` int(10) DEFAULT NULL,
  `videoheight` int(10) DEFAULT NULL,
  `videoaspect` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `videoframerate` float(7,4) DEFAULT NULL,
  `videolibrary` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`releaseID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `releaseaudio`;
CREATE TABLE `releaseaudio` (
  `ID` int(11) unsigned auto_increment,
  `releaseID` int(11) unsigned NOT NULL,
  `audioID` int(2) unsigned NOT NULL,
  `audioformat` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiomode` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiobitratemode` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiobitrate` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiochannels` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiosamplerate` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiolibrary` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiolanguage` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audiotitle` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY(`ID`),
  UNIQUE (`releaseID`,`audioID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `releasesubs`;
CREATE TABLE IF NOT EXISTS `releasesubs` (
  `ID` int(11) unsigned auto_increment,
  `releaseID` int(11) unsigned NOT NULL,
  `subsID` int(2) unsigned NOT NULL,
  `subslanguage` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY(`ID`),
  UNIQUE (`releaseID`,`subsID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;