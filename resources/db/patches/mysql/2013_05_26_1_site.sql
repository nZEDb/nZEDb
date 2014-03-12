INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('imdburl', 0);

UPDATE `site` set `value` = '52' where `setting` = 'sqlpatch';
