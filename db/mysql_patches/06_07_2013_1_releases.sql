INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('deletepossiblerelease', 0);
UPDATE `site` set `value` = '73' where `setting` = 'sqlpatch';
