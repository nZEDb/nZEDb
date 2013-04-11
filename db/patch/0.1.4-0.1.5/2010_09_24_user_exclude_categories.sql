CREATE TABLE `userexcat` (
  `ID` INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userID` INT NOT NULL ,
  `categoryID` INT NOT NULL,
  `createddate` DATETIME NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARSET latin1 COLLATE latin1_general_ci AUTO_INCREMENT=1 ;


CREATE UNIQUE INDEX ix_userexcat_usercat ON userexcat (userID, categoryID);