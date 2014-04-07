INSERT IGNORE INTO tmux (setting, value) VALUE ('scrape_cz', 0);
INSERT IGNORE INTO tmux (setting, value) VALUE ('scrape_efnet', 0);

UPDATE site SET value = '192' WHERE setting = 'sqlpatch';
