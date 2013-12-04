INSERT INTO site (setting, value) VALUES ('nntpretries', '10');

UPDATE site SET value = '145' WHERE setting = 'sqlpatch';
