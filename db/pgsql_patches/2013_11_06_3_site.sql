INSERT IGNORE INTO site (setting, value) VALUE ('anidbkey', '');

UPDATE site SET value = '139' WHERE setting = 'sqlpatch';
