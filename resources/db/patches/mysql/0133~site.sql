INSERT IGNORE INTO site (setting, value) VALUE ('tablepergroup', '0');

UPDATE site set value = '133' where setting = 'sqlpatch';
