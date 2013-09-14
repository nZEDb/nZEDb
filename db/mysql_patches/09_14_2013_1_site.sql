INSERT IGNORE INTO site (setting, value) VALUE ('fixnamesperrun', '1000');

UPDATE site set value = '123' where setting = 'sqlpatch';

