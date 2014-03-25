INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('zippath','');

UPDATE `site` set `value` = '106' where `setting` = 'sqlpatch';
