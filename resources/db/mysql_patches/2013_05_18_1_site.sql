INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('catwebdl', 0);

UPDATE `site` set `value` = '38' where `setting` = 'sqlpatch';
