DROP TABLE IF EXISTS `releasefiles`;
CREATE TABLE `releasefiles` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `releaseID` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(255) COLLATE utf8_unicode_ci NULL,
  `size` BIGINT UNSIGNED NOT NULL DEFAULT '0',
  `createddate` DATETIME DEFAULT NULL,
  `passworded` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_releasefiles_releaseID ON releasefiles (`releaseID`);
CREATE INDEX ix_releasefiles_name ON releasefiles (`name`);

DROP TABLE IF EXISTS `releaseextra`;
CREATE TABLE `releaseextra` (
  `releaseID` INT(11) UNSIGNED NOT NULL,
  `containerformat` VARCHAR(50) COLLATE utf8_unicode_ci NULL,
  `overallbitrate` VARCHAR(20) COLLATE utf8_unicode_ci NULL,
  `videoduration` VARCHAR(20) COLLATE utf8_unicode_ci NULL,
  `videoformat` VARCHAR(50) COLLATE utf8_unicode_ci NULL, 
  `videocodec` VARCHAR(50) COLLATE utf8_unicode_ci NULL, 
  `videowidth` INT(10) NULL, 
  `videoheight` INT(10) NULL,
  `videoaspect` VARCHAR(10) COLLATE utf8_unicode_ci NULL, 
  `videoframerate` FLOAT(7,4) NULL,
  `videolibrary` VARCHAR(50) NULL,
  `audioformat` VARCHAR(50) COLLATE utf8_unicode_ci NULL,
  `audiomode` VARCHAR(50) COLLATE utf8_unicode_ci NULL,
  `audiobitratemode` VARCHAR(50) COLLATE utf8_unicode_ci NULL,
  `audiobitrate` VARCHAR(10) COLLATE utf8_unicode_ci NULL,
  `audiochannels` VARCHAR(25) COLLATE utf8_unicode_ci NULL,
  `audiosamplerate` VARCHAR(25) COLLATE utf8_unicode_ci NULL, 
  `audiolibrary` VARCHAR(50) COLLATE utf8_unicode_ci NULL, 
  PRIMARY KEY (`releaseID`)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

DROP TABLE IF EXISTS `releaseextrafull`;
CREATE TABLE `releaseextrafull` (
  `releaseID` INT(11) UNSIGNED NOT NULL,
  `mediainfo` TEXT NULL,
  PRIMARY KEY (`releaseID`)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;