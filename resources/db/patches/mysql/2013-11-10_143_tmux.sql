INSERT IGNORE INTO tmux (setting, value) VALUE ('showprocesslist', 'FALSE');
INSERT IGNORE INTO tmux (setting, value) VALUE ('processupdate', '2');

UPDATE site SET value = '143' WHERE setting = 'sqlpatch';
