DROP INDEX IF EXISTS ix_releases_mergedreleases;
DROP INDEX IF EXISTS ix_releases_postdate;
DROP INDEX IF EXISTS ix_releases_nzbstatus;

DROP TRIGGER IF EXISTS check_insert ON releases;
DROP TRIGGER IF EXISTS check_update ON releases;

ALTER TABLE releases ADD request BOOL DEFAULT false;

CREATE INDEX ix_releases_status ON releases (nzbstatus, id, nfostatus, relnamestatus, passwordstatus, dehashstatus, reqidstatus, musicinfoID, consoleinfoID, bookinfoID, haspreview, hashed, request, categoryid);
CREATE INDEX ix_releases_postdate ON releases (name, searchname, id, postdate);
CREATE INDEX ix_releases_postdate_searchname ON releases (postdate, searchname);
CREATE INDEX ix_releases_postdate_name ON releases (postdate, name);



DROP FUNCTION IF EXISTS check_insert() CASCADE;
DROP FUNCTION IF EXISTS check_update() CASCADE;
DROP FUNCTION IF EXISTS hash_check() CASCADE;
DROP FUNCTION IF EXISTS request_check() CASCADE;

DROP TRIGGER IF EXISTS check_insert ON releases;
DROP TRIGGER IF EXISTS check_update ON releases;
DROP TRIGGER IF EXISTS hash_check ON releases;
DROP TRIGGER IF EXISTS request_check ON releases;

CREATE FUNCTION hash_check() RETURNS trigger AS $hash_check$ BEGIN IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true; ELSE SET NEW.hashed = false; END IF; END; $hash_check$ LANGUAGE plpgsql;
CREATE TRIGGER hash_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE hash_check();
CREATE FUNCTION request_check() RETURNS trigger AS $request_check$ BEGIN IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]' THEN SET NEW.request = true; END IF; END; $request_check$ LANGUAGE plpgsql;
CREATE TRIGGER request_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE request_check();

UPDATE releases SET request = true WHERE name ~ '^\\[[[:digit:]]+\\]';

UPDATE site SET value = '135' WHERE setting = 'sqlpatch';
