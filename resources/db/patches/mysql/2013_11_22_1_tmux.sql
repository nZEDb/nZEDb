UPDATE tmux SET value = 0 WHERE value = 'FALSE';
UPDATE tmux SET value = 1 WHERE value = 'TRUE';

INSERT IGNORE INTO site (setting, value) VALUE ('showdroppedyencparts', 0);
INSERT IGNORE INTO tmux (setting, value) VALUE ('usecache', 0);

UPDATE site SET value = '149' WHERE setting = 'sqlpatch';
