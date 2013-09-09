INSERT IGNORE INTO site (setting, value) values ('fixnamethreads', '1');

UPDATE site SET value = '118' WHERE setting = 'sqlpatch';
