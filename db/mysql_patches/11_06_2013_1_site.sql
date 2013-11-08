INSERT IGNORE INTO site (setting, value) VALUE ('replacenzbs', '0');

UPDATE site SET value = '137' WHERE setting = 'sqlpatch';
