INSERT IGNORE INTO tmux (setting, value) VALUE ('sharing_timer', '60');

UPDATE site SET value = '205' WHERE setting = 'sqlpatch';
