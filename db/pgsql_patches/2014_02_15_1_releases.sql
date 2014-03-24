ALTER TABLE releases ADD COLUMN nzbstatus BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE releases ADD COLUMN iscategorized BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE releases ADD COLUMN isrenamed BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE releases ADD COLUMN ishashed BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE releases ADD COLUMN isrequestid BOOLEAN NOT NULL DEFAULT FALSE;
UPDATE releases SET nzbstatus = TRUE WHERE (bitwise & 256) = 256;
UPDATE releases SET iscategorized = TRUE WHERE (bitwise & 1) = 1;
UPDATE releases SET isrenamed = TRUE WHERE (bitwise & 4) = 4;
UPDATE releases SET ishashed = TRUE WHERE (bitwise & 512) = 512;
UPDATE releases SET isrequestid = TRUE WHERE (bitwise & 1024) = 1024;

DROP INDEX ix_releases_status;
CREATE INDEX ix_releases_status ON releases (nzbstatus, iscategorized, isrenamed, nfostatus, ishashed, isrequestid, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid, imdbid, rageid);

DROP TRIGGER IF EXISTS check_insert ON releases;
DROP TRIGGER IF EXISTS check_update ON releases;

DROP FUNCTION IF EXISTS check_insert() CASCADE;
DROP FUNCTION IF EXISTS check_update() CASCADE;
DROP FUNCTION IF EXISTS hash_check() CASCADE;
DROP FUNCTION IF EXISTS request_check() CASCADE;

DROP TRIGGER IF EXISTS check_insert ON releases;
DROP TRIGGER IF EXISTS check_update ON releases;
DROP TRIGGER IF EXISTS hash_check ON releases;
DROP TRIGGER IF EXISTS request_check ON releases;

CREATE FUNCTION hash_check() RETURNS trigger AS $hash_check$ BEGIN IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END; $hash_check$ LANGUAGE plpgsql;
CREATE FUNCTION request_check() RETURNS trigger AS $request_check$ BEGIN IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]' THEN SET NEW.isrequestid = 1; END IF; END; $request_check$ LANGUAGE plpgsql;
CREATE TRIGGER request_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE request_check();
CREATE TRIGGER hash_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE hash_check();

UPDATE site SET value = '177' WHERE setting = 'sqlpatch';
