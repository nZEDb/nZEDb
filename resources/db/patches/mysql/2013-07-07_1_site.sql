ALTER TABLE `releases` ADD COLUMN `relstatus` TINYINT(4) NOT NULL DEFAULT 0;

UPDATE `site` set `value` = '92' where `setting` = 'sqlpatch';
