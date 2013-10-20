INSERT IGNORE INTO tmux (setting, value) VALUE ('NNTPPROXY', 'FALSE');

UPDATE site SET value = '131' WHERE setting = 'sqlpatch';
