CREATE TABLE `usermovies` (
  `ID` INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userID` int(16) NOT NULL,
  `imdbID` MEDIUMINT(7) UNSIGNED ZEROFILL NULL,
  `categoryID` VARCHAR(64) NULL DEFAULT NULL,
  `createddate` DATETIME NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_usermovies_userID ON usermovies (userID, `imdbID`);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('mymovies', 'My Movies', 
	'Your Movie Wishlist', 1, 78);