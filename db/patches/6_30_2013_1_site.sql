ALTER TABLE  `nzbs` ADD  `dateadded` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL;
UPDATE `nzbs` SET `dateadded` = now();

UPDATE `site` set `value` = '87' where `setting` = 'sqlpatch';
