DROP INDEX IF EXISTS "ix_releases_preid_searchname" CASCADE;
CREATE INDEX ix_releases_preid_searchname ON releases (preid, searchname);

UPDATE releases SET preid = 0 WHERE preid IS NULL;
ALTER TABLE `releases` CHANGE COLUMN `preid` `preid` bigint DEFAULT 0 NOT NULL;

UPDATE `site` set `value` = '179' where `setting` = 'sqlpatch';
