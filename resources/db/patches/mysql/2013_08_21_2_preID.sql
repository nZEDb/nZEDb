ALTER TABLE `releases` ADD COLUMN `preID` INT NULL;

CREATE INDEX ix_releases_preID ON releases (`preID`);

UPDATE `site` set `value` = '110' where `setting` = 'sqlpatch';
