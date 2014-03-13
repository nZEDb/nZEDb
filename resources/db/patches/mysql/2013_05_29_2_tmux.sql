INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('PROGRESSIVE', 'FALSE');

UPDATE `site` set `value` = '65' where `setting` = 'sqlpatch';
