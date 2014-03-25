INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('POWERLINE', 'FALSE');

UPDATE `site` set `value` = '51' where `setting` = 'sqlpatch';
