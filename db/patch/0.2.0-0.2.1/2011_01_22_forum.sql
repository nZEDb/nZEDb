CREATE TABLE IF NOT EXISTS `forumpost` (
  `ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `forumID` INT(11) NOT NULL DEFAULT '1',
  `parentID` INT(11) NOT NULL DEFAULT '0',
  `userID` INT(11) UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `locked` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `sticky` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `replies` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `createddate` DATETIME NOT NULL,
  `updateddate` DATETIME NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY `parentID` (`parentID`),
  KEY `userID` (`userID`),
  KEY `createddate` (`createddate`),
  KEY `updateddate` (`updateddate`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;



INSERT INTO `menu`
            (`href`,
             `title`,
             `tooltip`,
             `role`,
             `ordinal`,
             `menueval`)
VALUES ('forum',
        'Forum',
        'User Forum',
        '1',
        '75',
        '');
        
        
        