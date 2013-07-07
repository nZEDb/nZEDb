ALTER TABLE `releases` ADD `relstatus` TINYINT(4) NOT NULL DEFAULT 0;

UPDATE `site` set `value` = '93' where `setting` = 'sqlpatch';

