INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('menuposition','2');

UPDATE `site` set `value` = '25' where `setting` = 'sqlpatch';
