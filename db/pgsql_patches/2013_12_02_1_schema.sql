DROP FUNCTION IF EXISTS hash_check() CASCADE;
DROP FUNCTION IF EXISTS request_check() CASCADE;

CREATE FUNCTION hash_check() RETURNS trigger AS $hash_check$ BEGIN IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}' THEN SET NEW.bitwise = "((NEW.bitwise & ~512)|512)"; END IF; END; $hash_check$ LANGUAGE plpgsql;
CREATE FUNCTION request_check() RETURNS trigger AS $request_check$ BEGIN IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]' THEN SET NEW.bitwise = "((NEW.bitwise & ~1024)|1024)"; END IF; END; $request_check$ LANGUAGE plpgsql;

UPDATE releases SET bitwise = ((bitwise & ~512)|512) WHERE searchname ~ '[a-fA-F0-9]{32}' OR name ~ '[a-fA-F0-9]{32}';
UPDATE releases SET bitwise = ((bitwise & ~1024)|1024) WHERE name ~ '^\\[[[:digit:]]+\\]';

ALTER TABLE releases DROP COLUMN hashed;
ALTER TABLE releases DROP COLUMN request;

UPDATE site SET value = '152' WHERE setting = 'sqlpatch';
