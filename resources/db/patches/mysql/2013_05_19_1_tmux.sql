INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('WRITE_LOGS', 'FALSE');

UPDATE `site` set `value` = '39' where `setting` = 'sqlpatch';
