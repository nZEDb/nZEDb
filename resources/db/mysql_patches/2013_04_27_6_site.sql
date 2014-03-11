INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('grabstatus', 1);

UPDATE `site` set `value` = '8' where `setting` = 'sqlpatch';
