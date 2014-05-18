ALTER TABLE `predb` ADD COLUMN `requestid` INT NOT NULL DEFAULT '0';
ALTER TABLE `predb` ADD COLUMN `groupid` INT NOT NULL DEFAULT '0';
CREATE INDEX ix_predb_requestid on predb(requestid, groupid);

UPDATE site SET value = '157' WHERE setting = 'sqlpatch';
