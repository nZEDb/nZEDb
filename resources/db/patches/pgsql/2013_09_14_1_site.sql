INSERT INTO site (setting, value) VALUES ('fixnamesperrun', '10');

UPDATE site set value = '123' where setting = 'sqlpatch';
