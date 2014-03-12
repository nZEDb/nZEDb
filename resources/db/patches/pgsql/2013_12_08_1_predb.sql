ALTER TABLE `predb` ADD COLUMN requestid integer NOT NULL DEFAULT 0, ADD COLUMN groupid integer NOT NULL DEFAULT 0;
CREATE INDEX predb_requestid on predb(requestid, groupid);

UPDATE site SET value = '157' WHERE setting = 'sqlpatch';
