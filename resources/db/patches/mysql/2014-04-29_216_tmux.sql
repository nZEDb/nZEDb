INSERT IGNORE INTO tmux (setting, value) VALUE ('run_ircscraper', 0);
DELETE FROM tmux WHERE setting = 'scrape_cz' or setting = 'scrape_efnet';

UPDATE settings SET value = '216' WHERE setting = 'sqlpatch';