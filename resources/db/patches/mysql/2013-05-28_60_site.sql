INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('postdelay', '300');

UPDATE `site` set `value` = '60' where `setting` = 'sqlpatch';
