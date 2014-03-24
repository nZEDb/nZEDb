DROP INDEX IF EXISTS "ix_releases_preid_searchname" CASCADE;
CREATE INDEX ix_releases_preid_searchname ON releases (preid, searchname);

UPDATE releases SET preid = 0 WHERE preid IS NULL;
ALTER TABLE releases ALTER preid DROP DEFAULT;
ALTER TABLE releases ALTER preid TYPE bigint;
ALTER TABLE releases ALTER preid SET DEFAULT 0;
ALTER TABLE releases ALTER preid SET NOT NULL;

UPDATE site set value = '179' where setting = 'sqlpatch';
