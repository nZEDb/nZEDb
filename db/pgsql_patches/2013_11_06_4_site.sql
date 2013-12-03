INSERT IGNORE INTO site (setting, value) VALUE ('safepartrepair', '0');

UPDATE site SET value = '140' WHERE setting = 'sqlpatch';
