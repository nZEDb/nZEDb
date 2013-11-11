INSERT IGNORE INTO site (setting, value) VALUE ('nntpretries', '10');

UPDATE site SET value = '145' WHERE setting = 'sqlpatch';
