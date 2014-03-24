ALTER TABLE users ADD style VARCHAR(255) NULL DEFAULT NULL;

UPDATE site set value = '182' where setting = 'sqlpatch';