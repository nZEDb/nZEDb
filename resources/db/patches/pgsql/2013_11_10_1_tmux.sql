INSERT INTO tmux (setting, value) VALUES ('showprocesslist', 'FALSE');
INSERT INTO tmux (setting, value) VALUES ('processupdate', '2');

UPDATE site SET value = '143' WHERE setting = 'sqlpatch';
