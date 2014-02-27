ALTER TABLE `binaries` CHANGE COLUMN `partcheck` `partcheck` BOOLEAN DEFAULT FALSE;
ALTER TABLE `collections` CHANGE COLUMN `filecheck` `filecheck` smallint NOT NULL DEFAULT 0;

UPDATE `site` set `value` = '180' where `setting` = 'sqlpatch';
