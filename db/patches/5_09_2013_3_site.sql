INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('maxpartsprocessed','3');

UPDATE `site` set `value` = '31' where `setting` = 'sqlpatch';
