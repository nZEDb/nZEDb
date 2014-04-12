INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('nzbsplitlevel','1');

UPDATE `site` set `value` = '12' where `setting` = 'sqlpatch';
