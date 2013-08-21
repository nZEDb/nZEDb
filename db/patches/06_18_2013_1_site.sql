INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('grabnzbs', '0');

UPDATE `site` set `value` = '77' where `setting` = 'sqlpatch';
