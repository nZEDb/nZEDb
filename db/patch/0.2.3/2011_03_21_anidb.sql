DROP TABLE IF EXISTS `animetitles`;
CREATE TABLE `animetitles` 
(
  `anidbID` INT(7) UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `unixtime` INT(12) UNSIGNED NOT NULL,
  UNIQUE KEY `title` (`title`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;


DROP TABLE IF EXISTS `anidb`;
CREATE TABLE `anidb` 
(
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `anidbID` INT(7) UNSIGNED NOT NULL,
  `title` VARCHAR(128) NOT NULL,
  `type` VARCHAR(32) NOT NULL,
  `startdate` DATE NOT NULL,
  `enddate` DATE NOT NULL,
  `related` VARCHAR(1024) NOT NULL,
  `creators` VARCHAR(1024) NOT NULL,
  `description` TEXT NOT NULL,
  `rating` VARCHAR(5) NOT NULL,
  `picture` VARCHAR(16) NOT NULL,
  `categories` VARCHAR(1024) NOT NULL,
  `characters` VARCHAR(1024) NOT NULL,
  `epnos` VARCHAR(2048) NOT NULL,
  `airdates` TEXT NOT NULL,
  `episodetitles` TEXT NOT NULL,
  `unixtime` INT(12) UNSIGNED NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `anidbID` (`anidbID`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;


UPDATE category SET ID=5070, parentID=5000 WHERE title = 'Anime';
ALTER TABLE releases ADD `anidbID` INT NULL;
ALTER TABLE site ADD `lookupanidb` INT NOT NULL DEFAULT 1;

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal`)
VALUES ('anime', 'Anime', 'Browse Anime', 1, 44);