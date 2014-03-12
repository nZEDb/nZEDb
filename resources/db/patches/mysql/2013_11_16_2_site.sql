INSERT IGNORE INTO site (setting, value) VALUE ('maxgrabnzbs', 100);

UPDATE site SET value = '147' WHERE setting = 'sqlpatch';
