INSERT IGNORE INTO site (setting, value) VALUE ('fixnamesperrun', '10');

UPDATE site set value = '123' where setting = 'sqlpatch';
