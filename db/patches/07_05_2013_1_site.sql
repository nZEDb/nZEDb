ALTER TABLE `releases` ADD COLUMN `relstatus` TINYINT(4) NOT NULL DEFAULT 0;
CREATE INDEX ix_releases_dehashstatus ON releases(dehashstatus);

UPDATE `site` set `value` = '89' where `setting` = 'sqlpatch';
