INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('catlanguage','0');

UPDATE `site` set `value` = '30' where `setting` = 'sqlpatch';
