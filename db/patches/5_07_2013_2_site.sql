INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('crossposttime','2');

UPDATE `site` set `value` = '26' where `setting` = 'sqlpatch';
