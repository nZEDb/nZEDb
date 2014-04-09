INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('safebackfilldate', '2012-06-24');

UPDATE `site` set `value` = '42' where `setting` = 'sqlpatch';
