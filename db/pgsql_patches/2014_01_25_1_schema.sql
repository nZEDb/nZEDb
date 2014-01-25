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

CREATE FUNCTION hash_check() RETURNS trigger AS $hash_check$ BEGIN IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}' THEN SET NEW.bitwise = "((NEW.bitwise & ~512)|512)"; END IF; END; $hash_check$ LANGUAGE plpgsql;
CREATE FUNCTION request_check() RETURNS trigger AS $request_check$ BEGIN IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]' THEN SET NEW.bitwise = "((NEW.bitwise & ~1024)|1024)"; END IF; END; $request_check$ LANGUAGE plpgsql;
CREATE TRIGGER request_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE request_check();
CREATE TRIGGER hash_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE hash_check();

UPDATE releases SET request = true WHERE name ~ '^\\[[[:digit:]]+\\]';
UPDATE releases SET hashed = true WHERE name ~ '[a-fA-F0-9]{32}' OR searchname ~ '[a-fA-F0-9]{32}';

UPDATE site SET value = '171' WHERE setting = 'sqlpatch';
