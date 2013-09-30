INSERT IGNORE INTO tmux (setting, value) VALUE ('COLORS', 'FALSE');

UPDATE site SET value = '124' WHERE setting = 'sqlpatch';
