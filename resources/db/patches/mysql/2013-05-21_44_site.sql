INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('hashcheck', 0);

UPDATE `site` set `value` = '44' where `setting` = 'sqlpatch';
