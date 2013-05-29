ALTER TABLE `predb` ADD `releaseID` INT NULL AFTER `md5`;

CREATE INDEX ix_predb_releaseID ON predb(`releaseID`);

UPDATE `site` set `value` = '67' where `setting` = 'sqlpatch';
