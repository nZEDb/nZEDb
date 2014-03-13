DROP INDEX ix_predb_md5 ON predb;
ALTER IGNORE TABLE predb ADD CONSTRAINT ix_predb_md5 UNIQUE (md5);
UPDATE releases SET preid = NULL where preid IS NOT NULL;

UPDATE site SET value = '146' WHERE setting = 'sqlpatch';
