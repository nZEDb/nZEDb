INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('predbversion', 1);

UPDATE `site` set `value` = '68' where `setting` = 'sqlpatch';
