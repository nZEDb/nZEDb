INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('MONITOR_PATH', NULL);

UPDATE `site` set `value` = '36' where `setting` = 'sqlpatch';
