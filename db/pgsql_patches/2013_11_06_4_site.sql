INSERT INTO site (setting, value) VALUES ('safepartrepair', '0');

UPDATE site SET value = '140' WHERE setting = 'sqlpatch';
