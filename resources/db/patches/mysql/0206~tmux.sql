INSERT IGNORE INTO tmux (setting, value) VALUE ('run_sharing', 0);

UPDATE site SET value = '206' WHERE setting = 'sqlpatch';
