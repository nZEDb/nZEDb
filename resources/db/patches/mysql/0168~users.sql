DELETE FROM site where setting = 'cp_url';
DELETE FROM site where setting = 'cp_api';
ALTER TABLE users ADD cp_url VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE users ADD cp_api VARCHAR(255) NULL DEFAULT NULL;

UPDATE `site` SET value = '168' WHERE setting = 'sqlpatch';
