ALTER TABLE releases ADD hashed BOOL DEFAULT false;
CREATE INDEX ix_releases_hashed ON releases(hashed);
 UPDATE releases SET hashed = true WHERE searchname REGEXP '[a-fA-F0-9]{32}' OR name REGEXP '[a-fA-F0-9]{32}';

DROP TRIGGER IF EXISTS hash_check_insert;
DROP TRIGGER IF EXISTS hash_check_update;
DROP TRIGGER IF EXISTS check_insert;
DROP TRIGGER IF EXISTS check_update;

CREATE TRIGGER hash_check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true; ELSE SET NEW.hashed = false; END IF; END;
CREATE TRIGGER hash_check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true; ELSE SET NEW.hashed = false; END IF; END;

UPDATE site SET value = '120' WHERE setting = 'sqlpatch';
