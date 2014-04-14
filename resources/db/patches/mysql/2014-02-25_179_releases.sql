CREATE INDEX ix_releases_preid_searchname ON releases (preid, searchname);

UPDATE releases SET preid = 0 WHERE preid IS NULL;
ALTER TABLE `releases` CHANGE COLUMN `preid` `preid` INT UNSIGNED NOT NULL DEFAULT '0';

UPDATE `site` set `value` = '179' where `setting` = 'sqlpatch';
