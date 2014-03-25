ALTER TABLE  `groups` ADD  `backfill` TINYINT(1) NOT NULL DEFAULT '0' AFTER  `active` ;

UPDATE `site` set `value` = '41' where `setting` = 'sqlpatch';
