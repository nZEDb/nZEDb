INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('maxsizetopostprocess', 0) AFTER maxsizetoformrelease;

UPDATE `site` set `value` = '46' where `setting` = 'sqlpatch';


