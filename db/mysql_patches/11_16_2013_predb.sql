DROP INDEX ix_predb_md5 ON predb;
CREATE UNIQUE INDEX ix_predb_md5 ON predb(md5);

UPDATE site SET value = '146' WHERE setting = 'sqlpatch';
