DROP INDEX ix_predb_md5 ON predb;
ALTER IGNORE TABLE predb ADD UNIQUE INDEX ix_predb_md5(md5);
UPDATE releases SET preid = NULL where preid IS NOT NULL;

UPDATE site SET value = '146' WHERE setting = 'sqlpatch';
