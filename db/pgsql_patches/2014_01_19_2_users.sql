DELETE FROM site where setting = 'cp_url';
DELETE FROM site where setting = 'cp_api';
ALTER TABLE users ADD COLUMN "cp_url" character varying(255);
ALTER TABLE users ADD COLUMN "cp_api" character varying(255);

UPDATE `site` SET value = '168' WHERE setting = 'sqlpatch';