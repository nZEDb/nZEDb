UPDATE tmux SET value = 0 WHERE value = 'FALSE';
UPDATE tmux SET value = 1 WHERE value = 'TRUE';

INSERT INTO site (setting, value) VALUES ('showdroppedyencparts', 0);
INSERT INTO tmux (setting, value) VALUES ('usecache', 0);

UPDATE site SET value = '149' WHERE setting = 'sqlpatch';
