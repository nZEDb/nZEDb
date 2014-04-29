INSERT IGNORE INTO tmux (setting, value) VALUE ('run_ircscraper', 0);

UPDATE tmux SET value = (select max(value) FROM
(select * from tmux) as x WHERE setting = 'scrape_cz' OR setting = 'scrape_efnet')
WHERE setting = 'run_ircscraper';

DELETE FROM tmux WHERE setting = 'scrape_cz' or setting = 'scrape_efnet';

UPDATE settings SET value = '216' WHERE setting = 'sqlpatch';