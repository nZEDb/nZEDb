ALTER TABLE `releases` ADD COLUMN `nzb_guid` VARCHAR(50) NULL;

UPDATE `site` set `value` = '94' where `setting` = 'sqlpatch';
