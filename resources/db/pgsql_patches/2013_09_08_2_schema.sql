ALTER TABLE releases ADD hashed BOOL DEFAULT false;
CREATE INDEX ix_releases_hashed ON releases(hashed);
UPDATE releases SET hashed = true WHERE searchname ~ '[a-fA-F0-9]{32}' OR name ~ '[a-fA-F0-9]{32}';
DROP FUNCTION IF EXISTS hash_check() CASCADE;
DROP TRIGGER IF EXISTS hash_check ON releases;

CREATE FUNCTION hash_check() RETURNS trigger AS $hash_check$ BEGIN IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true; ELSE SET NEW.hashed = false; END IF; END; $hash_check$ LANGUAGE plpgsql;
CREATE TRIGGER hash_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE hash_check();

UPDATE site SET value = '120' WHERE setting = 'sqlpatch';
