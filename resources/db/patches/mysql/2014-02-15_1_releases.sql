ALTER TABLE `releases` ADD COLUMN `nzbstatus` BOOL NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `iscategorized` BOOL NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `isrenamed` BOOL NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `ishashed` BOOL NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `isrequestid` BOOL NOT NULL DEFAULT 0;
UPDATE releases SET nzbstatus = 1 WHERE (bitwise & 256) = 256;
UPDATE releases SET iscategorized = 1 WHERE (bitwise & 1) = 1;
UPDATE releases SET isrenamed = 1 WHERE (bitwise & 4) = 4;
UPDATE releases SET ishashed = 1 WHERE (bitwise & 512) = 512;
UPDATE releases SET isrequestid = 1 WHERE (bitwise & 1024) = 1024;

DROP INDEX ix_releases_status ON releases;
CREATE INDEX ix_releases_status ON releases (nzbstatus, iscategorized, isrenamed, nfostatus, ishashed, isrequestid, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid, imdbid, rageid);

DROP TRIGGER IF EXISTS check_insert;
DROP TRIGGER IF EXISTS check_update;

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.isrequestid = 1; END IF; END;
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.isrequestid = 1; END IF; END;

UPDATE site SET value = '177' WHERE setting = 'sqlpatch';
