INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('debuginfo', 0);

UPDATE `site` set `value` = '45' where `setting` = 'sqlpatch';
