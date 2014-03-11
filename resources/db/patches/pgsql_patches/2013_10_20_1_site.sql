INSERT INTO site (setting, value) VALUES ('tablepergroup', '0');

UPDATE site set value = '133' where setting = 'sqlpatch';
