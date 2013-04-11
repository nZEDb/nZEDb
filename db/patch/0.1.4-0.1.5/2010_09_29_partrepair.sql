CREATE TABLE IF NOT EXISTS `partrepair` (
  `ID` int(16) unsigned NOT NULL auto_increment,
  `numberID` int(11) unsigned NOT NULL,
  `groupID` int(11) unsigned NOT NULL,
  `attempts` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `ix_numberID_groupID` (`numberID`,`groupID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;