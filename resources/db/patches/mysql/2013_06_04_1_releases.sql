ALTER TABLE `releases` ADD `dehashstatus` TINYINT(1) NOT NULL DEFAULT '0' AFTER  `audiostatus` ;
CREATE INDEX ix_releases_dehashstatus ON releases(dehashstatus);
UPDATE `site` set `value` = '72' where `setting` = 'sqlpatch';
