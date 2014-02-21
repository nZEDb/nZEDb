ALTER TABLE `releases` CHANGE COLUMN `nzbstatus` `nzbstatus` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `iscategorized` `iscategorized` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `isrenamed` `isrenamed` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `ishashed` `ishashed` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `isrequestid` `isrequestid` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `jpgstatus` `jpgstatus` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `videostatus` `videostatus` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` CHANGE COLUMN `audiostatus` `audiostatus` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `proc_pp` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `proc_sorter` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `proc_par2` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `proc_nfo` BIT NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `proc_files` BIT NOT NULL DEFAULT 0;

UPDATE releases SET proc_pp = 1 WHERE (bitwise & 8) = 8;
UPDATE releases SET proc_sorter = 1 WHERE (bitwise & 16) = 16;
UPDATE releases SET proc_par2 = 1 WHERE (bitwise & 32) = 32;
UPDATE releases SET proc_nfo = 1 WHERE (bitwise & 64) = 64;
UPDATE releases SET proc_files = 1 WHERE (bitwise & 128) = 128;

ALTER TABLE `releases` DROP COLUMN `bitwise`;

UPDATE site SET value = '178' WHERE setting = 'sqlpatch';
