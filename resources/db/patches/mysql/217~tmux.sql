INSERT IGNORE INTO tmux (setting, value) VALUE ('run_ircscraper', 0);
INSERT IGNORE INTO tmux (setting, value) VALUE ('run_sharing', 0);
INSERT IGNORE INTO tmux (setting, value) VALUE ('sharing_timer', '60');

UPDATE settings SET value = '217' WHERE setting = 'sqlpatch';