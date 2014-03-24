INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('predbhashcheck', 0);

UPDATE `site` set `value` = '62' where `setting` = 'sqlpatch';
