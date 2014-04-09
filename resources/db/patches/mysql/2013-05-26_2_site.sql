INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('imdblanguage', 'en');

UPDATE `site` set `value` = '53' where `setting` = 'sqlpatch';
