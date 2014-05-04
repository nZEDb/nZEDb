INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('amazonsleep', 1000);

UPDATE `site` set `value` = '34' where `setting` = 'sqlpatch';
