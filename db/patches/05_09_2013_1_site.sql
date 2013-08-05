INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('maxpartsprocessed','1');

UPDATE `site` set `value` = '29' where `setting` = 'sqlpatch';
