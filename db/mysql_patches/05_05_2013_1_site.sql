INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('categorizeforeign','1');

UPDATE `site` set `value` = '22' where `setting` = 'sqlpatch';
