INSERT INTO site (setting, value) VALUES ('replacenzbs', '0');

UPDATE site SET value = '137' WHERE setting = 'sqlpatch';
