INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('maxsizetopostprocess', 100);

UPDATE `site` set `value` = '46' where `setting` = 'sqlpatch';
