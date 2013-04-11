alter table users add invites int not null default 0;
alter table users add invitedby int null;

DROP TABLE IF EXISTS `userinvite`;
CREATE TABLE `userinvite` (
  `ID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `guid` varchar(50) NOT NULL,
  `userID` int(11) UNSIGNED NOT NULL,
  `createddate` datetime not null,
  PRIMARY KEY (`ID`)
) ENGINE=MYISAM DEFAULT CHARSET latin1 COLLATE latin1_general_ci AUTO_INCREMENT=1 ;