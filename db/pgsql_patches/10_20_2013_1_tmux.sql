INSERT IGNORE INTO site (setting, value) VALUE ('nntpproxy', '0');

UPDATE site SET value = '133' WHERE setting = 'sqlpatch';
