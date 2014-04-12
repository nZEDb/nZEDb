ALTER TABLE `binaries` CHANGE COLUMN `partcheck` `partcheck` BIT NOT NULL DEFAULT 0;
ALTER TABLE `collections` CHANGE COLUMN `filecheck` `filecheck` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0;

UPDATE `site` set `value` = '180' where `setting` = 'sqlpatch';
