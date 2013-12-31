ALTER TABLE users ADD COLUMN "firstname" character varying(255), ADD COLUMN "lastname" character varying(255);

UPDATE `site` SET value = '159' WHERE setting = 'sqlpatch';
