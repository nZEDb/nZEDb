INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('COLORS_START', '1'), ('COLORS_END', '250'), ('COLORS_EXC', '4, 8, 9, 11, 15, 16, 17, 18, 19, 46, 47, 48, 49, 50, 51, 52, 53, 59, 60');

UPDATE `site` set `value` = '86' where `setting` = 'sqlpatch';
