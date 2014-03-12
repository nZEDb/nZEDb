INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('lookuppar2','0');

UPDATE `site` set `value` = '108' where `setting` = 'sqlpatch';
