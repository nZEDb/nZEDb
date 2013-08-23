INSERT IGNORE INTO tmux (setting, value) values ('MONITOR_PATH_A', NULL), ('MONITOR_PATH_B', NULL);

UPDATE `site` set `value` = '97' where `setting` = 'sqlpatch';
