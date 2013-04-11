DROP TABLE IF EXISTS `userseries`;
CREATE TABLE `userseries` (
  `ID` INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userID` int(16) NOT NULL,
  `rageID` int(16) NOT NULL,
  `createddate` DATETIME NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_userseries_userID ON userseries (userID, `rageID`);
