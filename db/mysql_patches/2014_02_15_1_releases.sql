ALTER TABLE `releases` ADD COLUMN `nzbstatus` BOOL NOT NULL DEFAULT 0;
UPDATE releases SET nzbstatus = 1 WHERE (bitwise & 256) = 256;
DROP INDEX ix_releases_status ON releases;
CREATE INDEX ix_releases_status ON releases (nzbstatus, nfostatus, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid, imdbid, rageid, groupid);

UPDATE site SET value = '177' WHERE setting = 'sqlpatch';
