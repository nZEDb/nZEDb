INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('alternate_nntp', '0');

UPDATE `site` set `value` = '81' where `setting` = 'sqlpatch';
