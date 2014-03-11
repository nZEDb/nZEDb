INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('passchkattempts', 1);

UPDATE `site` set `value` = '37' where `setting` = 'sqlpatch';
